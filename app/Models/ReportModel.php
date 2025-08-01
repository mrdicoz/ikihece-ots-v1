<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'lessons';

    /**
     * Aylık özet verilerini (kartlar için) getirir.
     */
    public function getMonthlySummary(int $year, int $month)
    {
        // İlgili aydaki tüm dersleri ve her derse katılan öğrenci sayısını tek seferde alalım.
        $lessons = $this->db->table('lessons l')
            ->select('l.id, (SELECT COUNT(ls.id) FROM lesson_students ls WHERE ls.lesson_id = l.id) as student_count')
            ->where('YEAR(l.lesson_date)', $year)
            ->where('MONTH(l.lesson_date)', $month)
            ->get()->getResultArray();

        $summary = [
            'total_hours'      => count($lessons),
            'total_individual' => 0,
            'total_group'      => 0,
        ];

        foreach ($lessons as $lesson) {
            if ($lesson['student_count'] == 1) {
                $summary['total_individual']++;
            } elseif ($lesson['student_count'] > 1) {
                $summary['total_group']++;
            }
        }
        
        // Toplam ders alan öğrenci sayısını bulalım.
        $totalStudentsResult = $this->db->table('lesson_students ls')
             ->join('lessons l', 'l.id = ls.lesson_id')
             ->where('YEAR(l.lesson_date)', $year)
             ->where('MONTH(l.lesson_date)', $month)
             ->select('COUNT(DISTINCT ls.student_id) as total')
             ->get()->getRow();

        $summary['total_students'] = $totalStudentsResult ? $totalStudentsResult->total : 0;

        return $summary;
    }

    /**
     * Öğrenci bazlı detaylı rapor verilerini getirir.
     */
    public function getDetailedStudentReport(int $year, int $month)
    {
        // 1. Adım: İlgili aydaki tüm ders-öğrenci eşleşmelerini alalım.
        $allLessonEntries = $this->db->table('lessons l')
            ->select('l.id as lesson_id, ls.student_id')
            ->join('lesson_students ls', 'ls.lesson_id = l.id')
            ->where('YEAR(l.lesson_date)', $year)
            ->where('MONTH(l.lesson_date)', $month)
            ->get()->getResultArray();

        if (empty($allLessonEntries)) {
            return [];
        }

        // 2. Adım: Her dersin kaç öğrenci tarafından alındığını sayalım.
        $lessonStudentCounts = [];
        foreach ($allLessonEntries as $entry) {
            if (!isset($lessonStudentCounts[$entry['lesson_id']])) {
                $lessonStudentCounts[$entry['lesson_id']] = 0;
            }
            $lessonStudentCounts[$entry['lesson_id']]++;
        }

        // 3. Adım: Her öğrencinin bireysel ve grup derslerini sayalım.
        $studentStats = [];
        foreach ($allLessonEntries as $entry) {
            $studentId = $entry['student_id'];
            if (!isset($studentStats[$studentId])) {
                $studentStats[$studentId] = ['individual_lessons' => 0, 'group_lessons' => 0];
            }

            $isIndividual = ($lessonStudentCounts[$entry['lesson_id']] == 1);
            if ($isIndividual) {
                $studentStats[$studentId]['individual_lessons']++;
            } else {
                $studentStats[$studentId]['group_lessons']++;
            }
        }

        // 4. Adım: Öğrenci bilgileriyle birleştirip son sonucu hazırlayalım.
        $studentIds = array_keys($studentStats);
        $students = $this->db->table('students')
            ->select('id, adi, soyadi')
            ->whereIn('id', $studentIds)
            ->get()->getResultArray();

        $report = [];
        foreach ($students as $student) {
            $stats = $studentStats[$student['id']];
            $report[] = [
                'student_name'       => $student['adi'] . ' ' . $student['soyadi'],
                'total_hours'        => $stats['individual_lessons'] + $stats['group_lessons'],
                'individual_lessons' => $stats['individual_lessons'],
                'group_lessons'      => $stats['group_lessons'],
            ];
        }

        return $report;
    }

    /**
     * Öğretmen bazlı aylık rapor verilerini getirir.
     */
    public function getDetailedTeacherReport(int $year, int $month)
    {
        // Bu sorgu yapısı daha basit olduğu için direkt SQL ile daha performanslı çalışır.
        $sql = "
            SELECT
                u.id,
                up.first_name,
                up.last_name,
                up.profile_photo,
                COUNT(DISTINCT l.id) as total_hours,
                SUM(CASE WHEN lc.student_count = 1 THEN 1 ELSE 0 END) as individual_lessons,
                SUM(CASE WHEN lc.student_count > 1 THEN 1 ELSE 0 END) as group_lessons,
                COUNT(DISTINCT ls.student_id) as total_students
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            JOIN auth_groups_users agu ON agu.user_id = u.id
            JOIN lessons l ON l.teacher_id = u.id
            JOIN lesson_students ls ON ls.lesson_id = l.id
            LEFT JOIN (
                SELECT lesson_id, COUNT(id) as student_count
                FROM lesson_students
                GROUP BY lesson_id
            ) as lc ON lc.lesson_id = l.id
            WHERE agu.group = 'ogretmen'
                AND YEAR(l.lesson_date) = ?
                AND MONTH(l.lesson_date) = ?
            GROUP BY u.id
            ORDER BY up.first_name ASC
        ";
        
        return $this->db->query($sql, [$year, $month])->getResultArray();
    }


    /**
     * Belirtilen ayda hiç ders almamış aktif öğrencileri getirir.
     */
    public function getStudentsWithNoLessons(int $year, int $month)
    {
        $subquery = $this->db->table('lessons l')
            ->select('ls.student_id')
            ->join('lesson_students ls', 'ls.lesson_id = l.id')
            ->where('YEAR(l.lesson_date)', $year)
            ->where('MONTH(l.lesson_date)', $month)
            ->groupBy('ls.student_id');

        return $this->db->table('students')
            ->select("id, CONCAT(adi, ' ', soyadi) as student_name, veli_anne_telefon, veli_baba_telefon")
            ->where('deleted_at', null)
            ->whereNotIn('id', $subquery)
            ->orderBy('student_name', 'ASC')
            ->get()->getResultArray();
    }
}