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
        // Her dersin öğrenci sayısını içeren bir alt sorgu
        $studentCountsSubquery = "(SELECT lesson_id, COUNT(id) as student_count FROM lesson_students GROUP BY lesson_id) as lc";

        return $this->db->table('students s')
            ->select("
                s.id, CONCAT(s.adi, ' ', s.soyadi) as student_name,
                SUM(CASE WHEN lc.student_count = 1 THEN 1 ELSE 0 END) as individual_lessons,
                SUM(CASE WHEN lc.student_count > 1 THEN 1 ELSE 0 END) as group_lessons,
                COUNT(l.id) as total_hours
            ")
            ->join('lesson_students ls', 'ls.student_id = s.id')
            ->join('lessons l', 'l.id = ls.lesson_id')
            ->join($studentCountsSubquery, 'lc.lesson_id = l.id', 'left')
            ->where('YEAR(l.lesson_date)', $year)
            ->where('MONTH(l.lesson_date)', $month)
            ->where('s.deleted_at', null)
            ->groupBy('s.id')
            ->orderBy('student_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Öğretmen bazlı aylık rapor verilerini getirir.
     * NİHAİ DÜZELTME: Bu fonksiyon artık kesin olarak doğru sayım yapar.
     */
    public function getDetailedTeacherReport(int $year, int $month)
    {
        // 1. Adım: İlgili aydaki dersleri, öğretmen ID'si ile birlikte bireysel/grup olarak sınıflandıran bir alt sorgu oluşturalım.
        // Bu alt sorgu, her ders için SADECE BİR satır döndürür.
        $classifiedLessons = $this->db->table('lessons l')
            ->select('
                l.teacher_id,
                l.id as lesson_id,
                IF((SELECT COUNT(*) FROM lesson_students ls WHERE ls.lesson_id = l.id) = 1, 1, 0) as is_individual,
                IF((SELECT COUNT(*) FROM lesson_students ls WHERE ls.lesson_id = l.id) > 1, 1, 0) as is_group
            ')
            ->where('YEAR(l.lesson_date)', $year)
            ->where('MONTH(l.lesson_date)', $month);

        // 2. Adım: Ana sorguyu, öğretmenleri bu sınıflandırılmış ders tablosuyla birleştirerek yapalım.
        // Bu yapı, her dersin sadece bir kez sayılmasını garanti eder.
        $builder = $this->db->table('users u');
        $builder->select('
            u.id,
            up.first_name,
            up.last_name,
            up.profile_photo,
            COUNT(cl.lesson_id) as total_hours,
            SUM(cl.is_individual) as individual_lessons,
            SUM(cl.is_group) as group_lessons,
            (SELECT COUNT(DISTINCT ls.student_id)
             FROM lesson_students ls
             JOIN lessons l_sub ON ls.lesson_id = l_sub.id
             WHERE l_sub.teacher_id = u.id AND YEAR(l_sub.lesson_date) = ' . $this->db->escape($year) . ' AND MONTH(l_sub.lesson_date) = ' . $this->db->escape($month) . ') as total_students,
            (SELECT COUNT(DISTINCT ls.student_id)
             FROM lesson_students ls
             JOIN lessons l_sub ON ls.lesson_id = l_sub.id
             WHERE l_sub.teacher_id = u.id AND YEAR(l_sub.lesson_date) = ' . $this->db->escape($year) . ' AND MONTH(l_sub.lesson_date) = ' . $this->db->escape($month) . '
               AND (SELECT COUNT(*) FROM lesson_students lsc WHERE lsc.lesson_id = l_sub.id) > 1
            ) as group_students_count
        ');
        $builder->join('user_profiles up', 'u.id = up.user_id', 'left');
        $builder->join('auth_groups_users agu', 'u.id = agu.user_id');
        $builder->join("({$classifiedLessons->getCompiledSelect()}) as cl", 'cl.teacher_id = u.id');
        $builder->where('agu.group', 'ogretmen');
        $builder->groupBy('u.id, up.first_name, up.last_name, up.profile_photo');
        $builder->orderBy('up.first_name', 'ASC');

        return $builder->get()->getResultArray();
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