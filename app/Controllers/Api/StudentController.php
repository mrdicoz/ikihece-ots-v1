<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class StudentController extends ResourceController
{
    protected $format = 'json';

    /**
     * Şoförün email adresine göre günlük öğrenci listesini ve ders saatlerini döndürür.
     * GET /api/mobile/students/daily?email=...&date=...
     */
// app/Controllers/Api/StudentController.php dosyasında bu fonksiyonu güncelle

    public function dailyList()
    {
        $email = $this->request->getGet('email');
        $date  = $this->request->getGet('date');
        $today = empty($date) ? date('Y-m-d') : $date;

        if (empty($email)) {
            return $this->fail('Email parametresi gerekli.', 400);
        }

        try {
            $db = \Config\Database::connect();
            
            $allTodayLessons = $db->table('students')
                ->select('
                    students.id, students.adi as first_name, students.soyadi as last_name, 
                    students.profile_image as photo, students.veli_anne_telefon as parent_phone_mother, 
                    students.veli_baba_telefon as parent_phone_father, students.google_konum as location_url, 
                    students.adres_detayi as address, cities.name as city_name,
                    districts.name as district_name, lessons.start_time, lessons.end_time
                ')
                ->distinct()
                ->join('lesson_students', 'lesson_students.student_id = students.id')
                ->join('lessons', 'lessons.id = lesson_students.lesson_id')
                ->join('cities', 'cities.id = students.city_id', 'left')
                ->join('districts', 'districts.id = students.district_id', 'left')
                ->where('lessons.lesson_date', $today)
                ->whereIn('students.servis', ['var', 'arasira'])
                ->where('students.deleted_at', null)
                ->orderBy('lessons.start_time', 'ASC')
                ->get()
                ->getResultArray();

            $groupedByTime = [];
            foreach ($allTodayLessons as $student) {
                $time = substr($student['start_time'], 0, 5);
                if (!isset($groupedByTime[$time])) {
                    $groupedByTime[$time] = [];
                }
                $groupedByTime[$time][] = $student;
            }

            // --- RADİKAL GÜNCELLEME: OTOMATİK ZAMANLAMA MANTIĞI (+/- 2 SAAT) ---
            $scheduleStart = null;
            $scheduleEnd = null;

            if (!empty($allTodayLessons)) {
                // En erken başlangıç saatini bul ve 2 saat geri al
                $firstLessonTime = min(array_column($allTodayLessons, 'start_time'));
                $scheduleStartObj = new \DateTime($firstLessonTime);
                $scheduleStartObj->modify('-2 hours');
                $scheduleStart = $scheduleStartObj->format('H:i');

                // En geç bitiş saatini bul ve 2 saat ileri al
                $lastLessonTime = max(array_column($allTodayLessons, 'end_time'));
                $scheduleEndObj = new \DateTime($lastLessonTime);
                $scheduleEndObj->modify('+2 hours');
                $scheduleEnd = $scheduleEndObj->format('H:i');
            }
            // --- BİTİŞ ---

            $stats = [
                'total_students' => count($allTodayLessons),
                'total_groups'   => count($groupedByTime),
            ];
            
            $data = [
                'students'        => $allTodayLessons,
                'grouped_by_time' => $groupedByTime,
                'stats'           => $stats,
                'date'            => $today,
                'working_hours'   => [
                    'start' => $scheduleStart,
                    'end'   => $scheduleEnd,
                ],
            ];

            return $this->respond([
                'status'  => 'success',
                'message' => 'Öğrenciler listelendi',
                'data'    => $data,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API Student Hata] ' . $e->getMessage() . ' Satır: ' . $e->getLine());
            return $this->fail('Sunucu hatası: ' . $e->getMessage(), 500);
        }
    }
}