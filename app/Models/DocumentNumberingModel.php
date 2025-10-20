<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentNumberingModel extends Model
{
    // Bu bir veritabanı tablosuna bağlı değil, bir yardımcı (helper) modeldir.
    protected $table = false;

    protected $documentModel;
    protected $institutionModel; // SystemSettingsModel yerine bunu kullanıyoruz.

    public function __construct()
    {
        parent::__construct();
        $this->documentModel = new DocumentModel();
        $this->institutionModel = new InstitutionModel(); // Model adını güncelledik.
    }

    /**
     * Şablon için bir sonraki evrak numarasını önerir
     */
    public function suggestNextNumber($templateId, $fillGaps = true)
    {
        $settings = $this->institutionModel->first();
        $prefix  = $settings->evrak_prefix ?? '';
        $startNo = $settings->evrak_baslangic_no ?? 1000;

        // Bu şablondan oluşturulmuş tüm numaraları çek
        $existingDocs = $this->documentModel
            ->select('document_number')
            //->where('template_id', $templateId) // Şimdilik şablona özel değil, genel bir sayaç kullanıyoruz. İleride gerekirse açılabilir.
            ->where('document_number IS NOT NULL', null, false)
            ->like('document_number', $prefix, 'after')
            ->orderBy('document_number', 'ASC')
            ->findAll();

        if (empty($existingDocs)) {
            return $prefix . $startNo;
        }

        // Numaralardan sadece sayı kısmını al
        $numbers = [];
        foreach ($existingDocs as $doc) {
            $numPart = str_replace($prefix, '', $doc->document_number);
            if(is_numeric($numPart)){
                $numbers[] = (int)$numPart;
            }
        }
        
        if(empty($numbers)){
             return $prefix . $startNo;
        }

        // Boşlukları doldur seçeneği aktifse
        if ($fillGaps) {
            sort($numbers);
            for ($i = $startNo; $i <= max($numbers); $i++) {
                if (!in_array($i, $numbers)) {
                    return $prefix . $i; // İlk boşluğu döndür
                }
            }
        }

        // Boşluk yoksa veya doldurmuyorsak, en son+1
        $nextNumber = max($numbers) + 1;
        return $prefix . $nextNumber;
    }
    
    /**
     * Evrak numarasının kullanılıp kullanılmadığını kontrol eder
     */
    public function isNumberUsed($documentNumber, $excludeDocId = null)
    {
        $builder = $this->documentModel->where('document_number', $documentNumber);

        if ($excludeDocId) {
            $builder->where('id !=', $excludeDocId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Özel numara uygun mu kontrol eder
     */
    public function validateCustomNumber($templateId, $customNumber, $suggestedNumber)
    {
        $settings = $this->institutionModel->first();
        $prefix  = $settings->evrak_prefix ?? '';

        // Prefix kontrolü
        if (!str_starts_with($customNumber, $prefix)) {
            return [
                'valid' => false,
                'message' => "Evrak numarası '{$prefix}' ile başlamalıdır."
            ];
        }

        // Kullanımda mı?
        if ($this->isNumberUsed($customNumber)) {
            return [
                'valid' => false,
                'message' => 'Bu evrak numarası zaten kullanılmış.'
            ];
        }

        // Önerilen numara değilse uyarı ver
        if ($customNumber !== $suggestedNumber) {
            return [
                'valid' => true,
                'warning' => "Sistem '{$suggestedNumber}' numarasını öneriyordu. Devam etmek istiyor musunuz?"
            ];
        }

        return ['valid' => true];
    }
}