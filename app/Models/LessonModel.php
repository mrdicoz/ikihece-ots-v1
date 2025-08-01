<?php namespace App\Models;

use CodeIgniter\Model;

class LessonModel extends Model
{
    protected $table            = 'lessons';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['teacher_id', 'lesson_date', 'start_time', 'end_time'];
    protected $useTimestamps    = true;

    /**
     * Belirli bir öğretmenin, belirtilen tarih aralığındaki tüm derslerini
     * ilişkili öğrenci bilgileriyle birlikte getirir.
     *
     * @param int    $teacherId
     * @param string $startDate 'Y-m-d' formatında başlangıç tarihi
     * @param string $endDate   'Y-m-d' formatında bitiş tarihi
     *
     * @return array
     */
    public function getLessonsForTeacherByWeek(int $teacherId, string $startDate, string $endDate)
    {
        // DEĞİŞİKLİK: 'lessons.title' kaldırıldı, 'lessons.end_time' eklendi.
        return $this->select('lessons.id, lessons.lesson_date, lessons.start_time, lessons.end_time, s.adi, s.soyadi, s.profile_image, ls.student_id')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
            ->join('students s', 's.id = ls.student_id')
            ->where('lessons.teacher_id', $teacherId)
            ->where('lessons.lesson_date >=', $startDate)
            ->where('lessons.lesson_date <=', $endDate)
            ->orderBy('lessons.lesson_date, lessons.start_time', 'ASC')
            ->asArray()
            ->findAll();
    }

    /**
     * Belirli bir öğretmenin, belirtilen tarihteki tüm derslerini
     * ilişkili öğrenci bilgileriyle birlikte getirir.
     *
     * @param int    $teacherId
     * @param string $date 'Y-m-d' formatında tarih
     *
     * @return array
     */
    public function getLessonsForTeacherByDate(int $teacherId, string $date)
    {
        return $this->select('lessons.start_time, lessons.end_time, s.adi, s.soyadi, s.profile_image, s.id as student_id')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
            ->join('students s', 's.id = ls.student_id')
            ->where('lessons.teacher_id', $teacherId)
            ->where('lessons.lesson_date', $date)
            ->orderBy('lessons.start_time', 'ASC')
            ->asArray()
            ->findAll();
    }

    // app/Models/LessonModel.php dosyasının içine, class'ın herhangi bir yerine ekle

    public function getLessonsForStudentByDate(int $studentId, string $date)
    {
        return $this->select('lessons.*, user_profiles.first_name as ogretmen_adi, 
            CASE 
                WHEN (SELECT COUNT(*) FROM lesson_students ls_count WHERE ls_count.lesson_id = lessons.id) > 1 THEN "Grup Dersi"
                ELSE "Bireysel Ders"
            END as ders_tipi')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lesson_students.student_id', $studentId)
            ->where('lessons.lesson_date', $date)
            ->orderBy('lessons.start_time', 'ASC')
            ->findAll();
    }

    // BU İKİ METODU LessonModel.php DOSYASININ SONUNA EKLE

    /**
     * Bir öğrencinin dersi olan tüm benzersiz yıl ve ayları getirir.
     * Filtre selectbox'larını doldurmak için kullanılır.
     */
    public function getLessonMonthsForStudent(int $studentId)
    {
        return $this->select('YEAR(lesson_date) as year, MONTH(lesson_date) as month')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('lesson_students.student_id', $studentId)
            ->distinct()
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->asArray()
            ->findAll();
    }

    /**
     * Bir öğrencinin seçilen yıl ve aydaki tüm derslerini detaylarıyla getirir.
     * Sonuç tablosunu doldurmak için kullanılır.
     */
    public function getLessonsForStudentByMonth(int $studentId, int $year, int $month)
    {
        return $this->select('lessons.lesson_date, lessons.start_time, user_profiles.first_name as teacher_first_name, user_profiles.last_name as teacher_last_name, 
            CASE 
                WHEN (SELECT COUNT(*) FROM lesson_students ls_count WHERE ls_count.lesson_id = lessons.id) > 1 THEN "Grup Dersi"
                ELSE "Bireysel Ders"
            END as lesson_type')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lesson_students.student_id', $studentId)
            ->where('YEAR(lessons.lesson_date)', $year)
            ->where('MONTH(lessons.lesson_date)', $month)
            ->orderBy('lessons.lesson_date', 'ASC')
            ->orderBy('lessons.start_time', 'ASC')
            ->findAll();
    }

}