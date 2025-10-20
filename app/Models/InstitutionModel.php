<?php

namespace App\Models;

use CodeIgniter\Model;

class InstitutionModel extends Model
{
    protected $table            = 'institutions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kurum_kodu', 'kurum_adi', 'kurum_kisa_adi', 'adresi', 'city_id', 'district_id', // DEĞİŞTİ
        'acilis_tarihi', 'web_sayfasi', 'epostasi', 'sabit_telefon', 'telefon',
        'kurucu_tipi', 'sirket_adi', 'kurucu_temsilci_tckn', 'kurum_vergi_dairesi',
        'kurum_vergi_no', 'google_konum', 'latitude', 'longitude',
        // FAZ 1'de eklenen alanlar
        'evrak_prefix',
        'evrak_baslangic_no',
        'kurum_muduru_user_id',
        'kurum_muduru_adi',
        'kurucu_mudur_adi',
        'kurum_logo_path',
        'kurum_qr_kod_path'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';


    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
