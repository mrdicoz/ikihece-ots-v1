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
        'week_type',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // Dates
    protected $useTimestamps = false;

    /**
     * Belirtilen öğretmenler ve günler için tüm sabit ders verilerini
     * öğrenci bilgileriyle (fotoğraf, lokasyon dahil) birlikte çeker.
     *
     * @param array $teacherIds Öğretmen ID'leri dizisi
     * @param array $dayNumbers Gün numaraları dizisi (1=Pzt, 2=Salı...)
     * @return array
     */
    public function getStructuredSchedule(array $teacherIds, array $dayNumbers): array
    {
        if (empty($teacherIds) || empty($dayNumbers)) {
            return [];
        }

        // --- DÜZELTME BURADA: Popover için city ve district tablolarını da dahil ediyoruz ---
        $results = $this->select('fixed_lessons.*, s.adi, s.soyadi, s.profile_image, c.name as city_name, d.name as district_name')
            ->join('students s', 's.id = fixed_lessons.student_id')
            ->join('cities c', 'c.id = s.city_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->whereIn('fixed_lessons.teacher_id', $teacherIds)
            ->whereIn('fixed_lessons.day_of_week', $dayNumbers)
            ->findAll();

        $structuredData = [];

        foreach ($results as $lesson) {
            $hour = date('H', strtotime($lesson['start_time']));
            $slotId = $lesson['teacher_id'] . '-' . $lesson['day_of_week'] . '-' . $hour;
            $weekType = $lesson['week_type'];

            // --- DÜZELTME BURADA: Gelen yeni verileri de diziye ekliyoruz ---
            $structuredData[$slotId][$weekType][] = [
                'student_id'    => $lesson['student_id'],
                'name'          => $lesson['adi'] . ' ' . $lesson['soyadi'],
                'photo'         => $lesson['profile_image'] ?? 'assets/images/user.jpg',
                'city'          => $lesson['city_name'],
                'district'      => $lesson['district_name'],
            ];
        }

        return $structuredData;
    }
}