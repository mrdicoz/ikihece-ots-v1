<?php

namespace App\Models;

use CodeIgniter\Model;

class FixedLessonModel extends Model
{
    protected $table            = 'fixed_lessons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'teacher_id',
        'student_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // Dates
    protected $useTimestamps = false; // Bu tabloda created_at/updated_at kullanmıyoruz.

    /**
     * YENİ FONKSİYON
     * Bir öğretmenin tüm sabit ders programını, öğrenci isimleriyle birlikte getirir.
     *
     * @param int $teacherId Öğretmenin kullanıcı ID'si
     * @return array
     */
    public function getFixedScheduleForTeacher(int $teacherId): array
    {
        return $this->select('fixed_lessons.day_of_week, fixed_lessons.start_time, fixed_lessons.end_time, s.adi, s.soyadi')
            ->join('students s', 's.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $teacherId)
            ->orderBy('fixed_lessons.day_of_week', 'ASC')
            ->orderBy('fixed_lessons.start_time', 'ASC')
            ->findAll();
    }

    /**
     * Belirli bir öğretmenin, belirli bir gündeki sabit ders programını
     * öğrenci bilgileriyle birlikte getirir.
     */
    public function getScheduleForTeacher(int $teacherId, int $dayOfWeek)
    {
        return $this->select('fixed_lessons.start_time, students.adi, students.soyadi')
                    ->join('students', 'students.id = fixed_lessons.student_id', 'left') // Öğrencisiz dersler için left join
                    ->where('fixed_lessons.teacher_id', $teacherId)
                    ->where('fixed_lessons.day_of_week', $dayOfWeek)
                    ->orderBy('fixed_lessons.start_time', 'ASC')
                    ->findAll();
    }
}