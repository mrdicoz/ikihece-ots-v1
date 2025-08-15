<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonHistoryModel extends Model
{
    protected $table            = 'lesson_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'teacher_name',
        'student_name',
        'lesson_date',
        'start_time'
    ];

    // Dates
    protected $useTimestamps = false;

     /**
     * YENİ FONKSİYON
     * Kullanıcı rolüne göre geçmiş ders kayıtlarını getirir.
     *
     * @param string $userFullName İşlemi yapan kullanıcının tam adı
     * @param string $userGroup    Kullanıcının grubu (örn: 'ogretmen')
     * @param int    $limit        Kaç kayıt getirileceği
     * @return array
     */
    public function getHistoryForUser(string $userFullName, string $userGroup, int $limit = 5): array
    {
        $builder = $this->orderBy('lesson_date', 'DESC')->orderBy('start_time', 'DESC');

        // Eğer kullanıcı öğretmen ise, sadece kendi verdiği derslerin geçmişini göster
        if ($userGroup === 'ogretmen') {
            $builder->where('teacher_name', $userFullName);
        }

        return $builder->findAll($limit);
    }

/**
     * GÜNCELLENMİŞ FONKSİYON
     * Haftanın belirli bir günü ve saatinde daha önce ders almış ve HALA AKTİF OLAN
     * öğrencileri öneri olarak getirir.
     *
     * @param int    $dayOfWeek  Haftanın günü (1: Pazartesi, 7: Pazar)
     * @param string $startTime  Ders başlangıç saati ('HH:MM:SS' formatında)
     * @return array
     */
    public function getStudentSuggestionsForSlot(int $dayOfWeek, string $startTime): array
    {
        $mysqlDayOfWeek = $dayOfWeek + 1;
        if ($mysqlDayOfWeek > 7) {
            $mysqlDayOfWeek = 1; // Pazar
        }

        return $this->select('lesson_history.student_name')
            ->distinct()
            // YENİ: students tablosu ile birleştirme
            ->join('students', "lesson_history.student_name = CONCAT(students.adi, ' ', students.soyadi)", 'inner')
            // YENİ: Sadece aktif (silinmemiş) öğrencileri getir
            ->where('students.deleted_at', null)
            ->where('DAYOFWEEK(lesson_history.lesson_date)', $mysqlDayOfWeek)
            ->like('lesson_history.start_time', substr($startTime, 0, 5), 'after')
            ->findAll(5);
    }
}