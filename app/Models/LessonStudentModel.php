<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonStudentModel extends Model
{
    protected $table            = 'lesson_students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'lesson_id',
        'student_id',
    ];

    // Bu ara tabloda created_at/updated_at kolonları olmadığı için timestamps kapalı.
    protected $useTimestamps = false;
}