<?php

namespace App\Controllers\AI;

class AdminAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Yönetici';
    }
}