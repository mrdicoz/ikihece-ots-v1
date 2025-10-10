<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $deletedField  = 'deleted_at';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    
    // VERİTABANI İLE BİREBİR UYUMLU, GÜNCEL SÜTUN LİSTESİ
    protected $allowedFields    = [
        'adi', 'soyadi', 'tckn', 'cinsiyet', 'dogum_tarihi', 'iletisim', 
        'adres_detayi', 'city_id', 'district_id', 'google_konum', 
        'veli_baba', 'veli_baba_telefon', 'veli_baba_tc', 
        'veli_anne', 'veli_anne_telefon', 'veli_anne_tc', 
        'servis', 'mesafe', 'orgun_egitim', 'egitim_sekli', 'egitim_programi', 
        'ram', 'ram_baslagic', 'ram_bitis', 'ram_raporu', 
        'hastane_adi', 'hastane_raporu_baslama_tarihi', 'hastane_raporu_bitis_tarihi', 
        'hastane_randevu_tarihi', 'hastane_randevu_saati', 'hastane_aciklama', 
        'profile_image', 'normal_bireysel_hak', 'normal_grup_hak', 
        'telafi_bireysel_hak', 'telafi_grup_hak'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

// Validation
protected $validationRules      = [
    'adi'        => 'required|string|max_length[100]',
    'soyadi'     => 'required|string|max_length[100]',
    'tckn'       => 'required|exact_length[11]|is_unique[students.tckn,id,{id}]', // ESKİ HALİNE GETİRİN    
    'iletisim'   => 'required',
    'city_id'    => 'required|integer|greater_than[0]',
    'district_id'=> 'required|integer|greater_than[0]',
    'egitim_programi' => 'required|array|min_length[1]',
];

protected $validationMessages   = [
    'adi' => [
        'required' => 'Öğrenci Adı alanı zorunludur.',
    ],
    'soyadi' => [
        'required' => 'Öğrenci Soyadı alanı zorunludur.',
    ],
    'tckn' => [
        'required'     => 'T.C. Kimlik Numarası zorunludur.',
        'exact_length' => 'T.C. Kimlik Numarası tam 11 haneli olmalıdır.',
        'is_unique'    => 'Bu T.C. Kimlik Numarası zaten başka bir öğrenciye kayıtlı.', // ESKİ HALİ
    ],
    'iletisim' => [
        'required' => 'İletişim (Telefon) alanı zorunludur.'
    ],
    'city_id' => [
        'required'      => 'İl seçimi zorunludur.',
        'greater_than'  => 'Lütfen geçerli bir il seçiniz.',
    ],
    'district_id' => [
        'required'      => 'İlçe seçimi zorunludur.',
        'greater_than'  => 'Lütfen geçerli bir ilçe seçiniz.',
    ],
    'egitim_programi' => [
        'required'   => 'En az bir eğitim programı seçilmelidir.',
        'min_length' => 'En az bir eğitim programı seçilmelidir.',
    ],
];
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

/**
 * TC kimlik numarasının soft delete durumuna göre müsait olup olmadığını kontrol eder
 */
public function isTcAvailable($tc, $exceptId = null): bool
{
    $builder = $this->builder();
    $builder->where('tckn', $tc)
            ->where('deleted_at IS NULL');
    
    if ($exceptId) {
        $builder->where('id !=', $exceptId);
    }
    
    return $builder->countAllResults() === 0;
}

    /**
     * Bir öğretmenin ders programına eklediği (ders verdiği) tüm öğrencileri,
     * her öğrenci sadece bir kez görünecek şekilde listeler.
     * Bu sorgu, öğrenciler ve dersler arasındaki ilişkiyi "lesson_students" 
     * pivot tablosu üzerinden kurar.
     *
     * @param int $teacherId Öğretmenin kullanıcı ID'si
     * @return array
     */
    public function getStudentsForTeacher(int $teacherId): array
    {
        return $this->select('students.*')
                    ->distinct()
                    ->join('lesson_students', 'lesson_students.student_id = students.id') // YENİ: Pivot tabloya join
                    ->join('lessons', 'lessons.id = lesson_students.lesson_id') // YENİ: Pivot'tan ana lessons tablosuna join
                    ->where('lessons.teacher_id', $teacherId)
                    ->orderBy('students.adi', 'ASC')
                    ->findAll();
    }

        /**
     * Belirtilen öğrencinin, belirtilen öğretmene ait olup olmadığını kontrol eder.
     * @param int $studentId Kontrol edilecek öğrencinin ID'si
     * @param int $teacherId Kontrol edilecek öğretmenin ID'si
     * @return bool
     */
    public function isStudentOfTeacher(int $studentId, int $teacherId): bool
    {
        $result = $this->select('students.id')
                       ->join('lesson_students', 'lesson_students.student_id = students.id')
                       ->join('lessons', 'lessons.id = lesson_students.lesson_id')
                       ->where('lessons.teacher_id', $teacherId)
                       ->where('students.id', $studentId)
                       ->countAllResults();

        return $result > 0;
    }

    // app/Models/StudentModel.php (Dosyanın sonuna eklendi)
    /**
     * Verilen T.C. Kimlik Numarasına sahip velinin (anne veya baba)
     * tüm öğrencilerini getirir.
     *
     * @param string $parentTckn Velinin T.C. Kimlik Numarası
     * @return array
     */
    public function getChildrenOfParent(string $parentTckn): array
    {
        if (empty($parentTckn)) {
            return [];
        }

        return $this->where('veli_anne_tc', $parentTckn)
                    ->orWhere('veli_baba_tc', $parentTckn)
                    ->findAll();
    }

    // app/Models/StudentModel.php dosyasının içine, class'ın herhangi bir yerine ekle

    public function getTeachersForStudent(int $studentId)
    {
        return $this->distinct()
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo')
            ->join('lesson_students', 'lesson_students.student_id = students.id')
            ->join('lessons', 'lessons.id = lesson_students.lesson_id')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('students.id', $studentId)
            ->findAll();
    }

    /**
 * Belirtilen ayda silinen öğrencileri getirir (Soft Delete)
 */
public function getDeletedStudentsThisMonth(int $year, int $month): array
{
    return $this->select('id, adi, soyadi, deleted_at')
                ->where('YEAR(deleted_at)', $year)
                ->where('MONTH(deleted_at)', $month)
                ->where('deleted_at IS NOT NULL')
                ->withDeleted()
                ->orderBy('deleted_at', 'DESC')
                ->findAll();
}

/**
 * Belirtilen ayda yeni kayıt olan öğrencileri getirir
 */
public function getNewStudentsThisMonth(int $year, int $month): array
{
    return $this->select('id, adi, soyadi, created_at')
                ->where('YEAR(created_at)', $year)
                ->where('MONTH(created_at)', $month)
                ->where('deleted_at IS NULL')
                ->orderBy('created_at', 'DESC')
                ->findAll();
}
}