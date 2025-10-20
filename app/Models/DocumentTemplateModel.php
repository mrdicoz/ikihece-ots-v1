<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentTemplateModel extends Model
{
    protected $table            = 'document_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_id', 'name', 'description', 'content',
        'static_fields', 'dynamic_fields', 'has_number',
        'allow_custom_number', 'fill_gaps', 'active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getTemplatesByCategory($categoryId)
    {
        return $this->where(['category_id' => $categoryId, 'active' => 1])->findAll();
    }

    public function getTemplateWithCategory($id)
    {
        return $this->select('document_templates.*, document_categories.name as category_name')
            ->join('document_categories', 'document_categories.id = document_templates.category_id')
            ->find($id);
    }
}