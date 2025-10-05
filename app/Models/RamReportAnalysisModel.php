<?php

namespace App\Models;

use CodeIgniter\Model;

class RamReportAnalysisModel extends Model
{
    protected $table = 'ram_report_analysis';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'ram_text_content', 'total_memory', 
        'available_memory', 'memory_info', 'analyzed_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}