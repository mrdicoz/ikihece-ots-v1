<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentAbsenceModel extends Model
{
    protected $table            = 'student_absences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'teacher_id',
        'lesson_date',
        'start_time',
        'end_time',
        'reason',
        'created_by'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function getAbsencesByMonthYear($month, $year, $studentId = null)
    {
        $builder = $this->select('student_absences.*, s.adi as student_name, s.soyadi as student_surname, up.first_name as teacher_first_name, up.last_name as teacher_last_name')
                        ->join('students s', 's.id = student_absences.student_id')
                        ->join('users u', 'u.id = student_absences.teacher_id', 'left')
                        ->join('user_profiles up', 'up.user_id = u.id', 'left')
                        ->where('MONTH(student_absences.lesson_date)', $month)
                        ->where('YEAR(student_absences.lesson_date)', $year);

        if ($studentId) {
            $builder->where('student_absences.student_id', $studentId);
        }

        return $builder->orderBy('student_absences.lesson_date', 'ASC')
                       ->findAll();
    }
}
