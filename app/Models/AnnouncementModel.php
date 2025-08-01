<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table            = 'announcements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    //protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'body',
        'author_id',
        'target_group',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Belirtilen gruplara yönelik yayınlanmış son duyuruları getirir.
     *
     * @param array $groups ['all', 'ogretmen'] gibi
     * @param int   $limit
     *
     * @return array
     */
    public function getLatestAnnouncementsForGroups(array $groups, int $limit = 5)
    {
        return $this->where('status', 'published')
                    ->whereIn('target_group', $groups)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }
}