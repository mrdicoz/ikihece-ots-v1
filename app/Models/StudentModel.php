<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
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
    protected $deletedField  = 'deleted_at';

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
}