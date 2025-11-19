<?php

namespace App\Controllers\AI;

class YoneticiAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Yönetici';
    }
}