<?php namespace App\Models;

use CodeIgniter\Model;

class LessonStudentModel extends Model
{
    protected $table            = 'lesson_students';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['lesson_id', 'student_id'];
    protected $useTimestamps    = false;
}