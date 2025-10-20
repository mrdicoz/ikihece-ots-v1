<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table            = 'documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'template_id', 'document_number', 'subject',
        'form_data', 'rendered_html', 'created_by'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getDocumentsWithDetails($userId = null, $isAdmin = false)
    {
        $builder = $this->select('
            documents.*,
            document_templates.name as template_name,
            document_categories.name as category_name,
            users.username as creator_name
        ')
        ->join('document_templates', 'document_templates.id = documents.template_id')
        ->join('document_categories', 'document_categories.id = document_templates.category_id')
        ->join('users', 'users.id = documents.created_by');

        // Admin ve yönetici hariç sadece kendi belgelerini görsün
        if (!$isAdmin && $userId) {
            $builder->where('documents.created_by', $userId);
        }

        return $builder->orderBy('documents.created_at', 'DESC')->findAll();
    }
}