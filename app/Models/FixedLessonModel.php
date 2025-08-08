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
}