<?php

namespace App\Controllers\AI;

class MudurAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Müdür';
    }
}