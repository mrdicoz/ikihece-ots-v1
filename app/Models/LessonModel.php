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

}