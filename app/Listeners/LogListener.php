<?php

namespace App\Listeners;

use App\Models\LogModel;
use CodeIgniter\Events\Events;

class LogListener
{
    /**
     * Bir kullanıcı oluşturulduğunda log kaydı tutar.
     *
     * @param object $createdUser Oluşturulan kullanıcı nesnesi/dizisi
     * @param object $actor       İşlemi yapan kullanıcı (auth()->user())
     */
    public function handleUserCreation($createdUser, $actor)
    {
        $logModel = new LogModel();

        $message = "Kullanıcı '{$actor->username}' (ID: {$actor->id}), '{$createdUser->username}' (ID: {$createdUser->id}) adlı yeni bir kullanıcı oluşturdu.";

        $logModel->insert([
            'user_id'    => $actor->id,
            'event'      => 'user.created',
            'message'    => $message,
            'ip_address' => request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Bir kullanıcı güncellendiğinde log kaydı tutar.
     *
     * @param object $updatedUser Güncellenen kullanıcı nesnesi
     * @param object $actor       İşlemi yapan kullanıcı
     */
    public function handleUserUpdate($updatedUser, $actor)
    {
        $logModel = new LogModel();

        $message = "Kullanıcı '{$actor->username}' (ID: {$actor->id}), '{$updatedUser->username}' (ID: {$updatedUser->id}) adlı kullanıcının bilgilerini güncelledi.";

        $logModel->insert([
            'user_id'    => $actor->id,
            'event'      => 'user.updated',
            'message'    => $message,
            'ip_address' => request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Bir kullanıcı silindiğinde log kaydı tutar.
     *
     * @param object $deletedUser Silinen kullanıcı nesnesi
     * @param object $actor       İşlemi yapan kullanıcı
     */
    public function handleUserDeletion($deletedUser, $actor)
    {
        $logModel = new LogModel();

        $message = "Kullanıcı '{$actor->username}' (ID: {$actor->id}), '{$deletedUser->username}' (ID: {$deletedUser->id}) adlı kullanıcıyı sildi.";

        $logModel->insert([
            'user_id'    => $actor->id,
            'event'      => 'user.deleted',
            'message'    => $message,
            'ip_address' => request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    // --- YENİ ÖĞRENCİ METODLARI ---

    /**
     * Bir öğrenci oluşturulduğunda log kaydı tutar.
     * @param array $student    Oluşturulan öğrencinin veri dizisi
     * @param object $actor     İşlemi yapan kullanıcı
     */
    public function handleStudentCreation($student, $actor)
    {
        $this->log(
            $actor->id,
            'student.created',
            "Kullanıcı '{$actor->username}', '{$student['adi']} {$student['soyadi']}' (ID: {$student['id']}) adlı yeni bir öğrenci kaydı oluşturdu."
        );
    }

    /**
     * Bir öğrenci güncellendiğinde log kaydı tutar.
     * @param array $student    Güncellenen öğrencinin veri dizisi
     * @param object $actor     İşlemi yapan kullanıcı
     */
    public function handleStudentUpdate($student, $actor)
    {
        $this->log(
            $actor->id,
            'student.updated',
            "Kullanıcı '{$actor->username}', '{$student['adi']} {$student['soyadi']}' (ID: {$student['id']}) adlı öğrencinin bilgilerini güncelledi."
        );
    }

    /**
     * Bir öğrenci silindiğinde log kaydı tutar.
     * @param array $student    Silinen öğrencinin veri dizisi
     * @param object $actor     İşlemi yapan kullanıcı
     */
    public function handleStudentDeletion($student, $actor)
    {
        $this->log(
            $actor->id,
            'student.deleted',
            "Kullanıcı '{$actor->username}', '{$student['adi']} {$student['soyadi']}' (ID: {$student['id']}) adlı öğrenci kaydını sildi."
        );
    }


    /**
     * Log verisini veritabanına kaydeden yardımcı metod.
     */
    private function log(int $userId, string $event, string $message): void
    {
        $logModel = new LogModel();
        $logModel->insert([
            'user_id'    => $userId,
            'event'      => $event,
            'message'    => $message,
            'ip_address' => request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }


}

    