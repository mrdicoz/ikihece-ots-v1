<?php namespace App\Models;

use CodeIgniter\Model;

class LessonModel extends Model
{
    protected $table            = 'lessons';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['teacher_id', 'lesson_date', 'start_time', 'end_time'];
    protected $useTimestamps    = true;
}