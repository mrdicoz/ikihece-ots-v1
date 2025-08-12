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
}