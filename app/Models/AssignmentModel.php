<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentModel extends Model
{
    protected $table            = 'user_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $protectFields    = true;
    protected $allowedFields    = ['manager_user_id', 'managed_user_id'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Belirli bir sekretere (manager) atanmış tüm öğretmenlerin ID'lerini getirir.
     *
     * @param int $managerId Sekreterin kullanıcı ID'si
     * @return array Atanmış öğretmenlerin ID listesi
     */
    public function getAssignedTeacherIds(int $managerId): array
    {
        return $this->where('manager_user_id', $managerId)
                    ->findColumn('managed_user_id') ?? [];
    }

    /**
     * Bir sekretere yeni öğretmenler atar.
     * Önceki tüm atamaları siler ve yenilerini ekler (senkronizasyon).
     *
     * @param int   $managerId  Sekreterin kullanıcı ID'si
     * @param array $teacherIds Atanacak öğretmenlerin ID listesi
     */
    public function syncAssignments(int $managerId, array $teacherIds): void
    {
        // Önce bu sekretere ait tüm eski kayıtları sil
        $this->where('manager_user_id', $managerId)->delete();

        // Eğer atanacak yeni öğretmen varsa, toplu olarak ekle
        if (!empty($teacherIds)) {
            $dataToInsert = [];
            foreach ($teacherIds as $teacherId) {
                $dataToInsert[] = [
                    'manager_user_id' => $managerId,
                    'managed_user_id' => $teacherId,
                ];
            }
            $this->insertBatch($dataToInsert);
        }
    }
}