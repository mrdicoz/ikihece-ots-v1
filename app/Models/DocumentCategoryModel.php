<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentCategoryModel extends Model
{
    protected $table            = 'document_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'description', 'order', 'active'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Sadece aktif olan ve sıralanmış kategorileri getirir.
     */
    public function getActiveCategories()
    {
        return $this->where('active', 1)->orderBy('order', 'ASC')->findAll();
    }
}