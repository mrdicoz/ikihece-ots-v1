<?php

namespace App\Controllers\AI;

class SekreterAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Sekreter';
    }
}