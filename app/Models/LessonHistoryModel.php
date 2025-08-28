<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonHistoryModel extends Model
{
    protected $table            = 'lesson_history';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'lesson_date',
        'start_time',
        'student_name',
        'student_program',
        'teacher_name',
        'teacher_branch'
    ];

    /**
     * Kullanıcı rolüne göre geçmiş ders kayıtlarını getirir.
     * Bu fonksiyonu isteğin üzerine koruyoruz.
     *
     * @param string $userFullName İşlemi yapan kullanıcının tam adı
     * @param string $userGroup    Kullanıcının grubu (örn: 'ogretmen')
     * @param int    $limit        Kaç kayıt getirileceği
     * @return array
     */
    public function getHistoryForUser(string $userFullName, string $userGroup, int $limit = 5): array
    {
        $builder = $this->orderBy('lesson_date', 'DESC')->orderBy('start_time', 'DESC');

        if ($userGroup === 'ogretmen') {
            $builder->where('teacher_name', $userFullName);
        }

        return $builder->findAll($limit);
    }

    /**
     * Belirtilen öğretmenin geçmiş ders kaydı olup olmadığını kontrol eder.
     * @param string $teacherFullName Öğretmenin tam adı
     * @return bool
     */
    public function teacherHasHistory(string $teacherFullName): bool
    {
        if (empty($teacherFullName)) {
            return false;
        }
        return $this->where('teacher_name', $teacherFullName)->countAllResults() > 0;
    }

    /**
     * Belirli bir öğretmenin, gün ve saatteki kendi geçmişine göre öğrenci önerir.
     * @param string $teacherFullName Öğretmenin tam adı
     * @param int    $dayOfWeek Haftanın günü (1=Pzt, 7=Paz)
     * @param string $startTime Başlangıç saati
     * @return array
     */
    public function getSuggestionsByTeacherHistory(string $teacherFullName, int $dayOfWeek, string $startTime): array
    {
        $mysqlDayOfWeek = ($dayOfWeek % 7) + 1; // PHP gününü MySQL gününe çevirir (Pazar=1)

        return $this->select('student_name, COUNT(id) as lesson_count')
            ->where('teacher_name', $teacherFullName)
            ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
            ->where('start_time', $startTime)
            ->groupBy('student_name')
            ->orderBy('lesson_count', 'DESC')
            ->findAll(10);
    }
    
    /**
     * Belirtilen kurallara göre branş ve program eşleşmesi yaparak öğrenci önerir.
     * @param string $branch Öğretmenin branşı
     * @param int    $dayOfWeek Haftanın günü (1=Pzt, 7=Paz)
     * @param string $startTime Başlangıç saati
     * @return array
     */
    public function getSuggestionsByBranch(string $branch, int $dayOfWeek, string $startTime): array
    {
        $mysqlDayOfWeek = ($dayOfWeek % 7) + 1;

        $builder = $this->select('student_name, student_program, COUNT(id) as lesson_count')
                        ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
                        ->where('start_time', $startTime);

        // Belirttiğin branş-program eşleştirme kuralları
        if ($branch === 'Fizyoterapist') {
            $builder->where('student_program', 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı');
        } elseif (in_array($branch, ['Dil ve Konuşma Bozuklukları Uzmanı', 'Odyoloji ve Konuşma Bozuklukları Uzmanı'])) {
            $builder->where('student_program', 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı');
        }
        // "Uzman Öğretici" ve "Özel Eğitim Alanı Öğretmeni" için program kısıtlaması yok, tümünü getirecek.

        return $builder->groupBy('student_name, student_program')
                       ->orderBy('lesson_count', 'DESC')
                       ->findAll(10);
    }
}