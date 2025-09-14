<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentEvaluationModel extends Model
{
    protected $table            = 'student_evaluations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['student_id', 'teacher_id', 'teacher_snapshot_name', 'evaluation'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Belirli bir öğrenciye ait tüm değerlendirmeleri,
     * değerlendirmeyi yapan öğretmenin bilgileriyle birlikte getirir.
     *
     * @param int $studentId Öğrenci ID'si
     * @return array
     */
    public function getEvaluationsForStudent(int $studentId): array
    {
        // DÜZELTME 2: Öğretmen branşı için doğru sütun adı ('branch') kullanıldı.
        return $this->select('
                student_evaluations.*, 
                user_profiles.first_name, 
                user_profiles.last_name, 
                user_profiles.profile_photo,
                user_profiles.branch as teacher_title,
                student_evaluations.teacher_snapshot_name
            ')
            ->join('users', 'users.id = student_evaluations.teacher_id', 'left')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('student_evaluations.student_id', $studentId)
            ->orderBy('student_evaluations.created_at', 'DESC')
            ->findAll();
    }

    /**
     * DÜZELTME 1: Eksik olan getUniqueEvaluators metodu eklendi.
     * Bir öğrenciyi değerlendiren benzersiz öğretmenlerin listesini getirir.
     * Filtre menüsünü doldurmak için kullanılır.
     * @param int $studentId
     * @return array
     */
    public function getUniqueEvaluators(int $studentId): array
    {
        return $this->distinct()
            ->select('u.id, se.teacher_snapshot_name as name')
            ->from('student_evaluations se')
            ->join('users u', 'u.id = se.teacher_id')
            ->where('se.student_id', $studentId)
            ->where('se.teacher_id IS NOT NULL')
            ->orderBy('se.teacher_snapshot_name', 'ASC')
            ->findAll();
    }
}