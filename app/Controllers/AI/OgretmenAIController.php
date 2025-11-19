<?php

namespace App\Controllers\AI;

class OgretmenAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Öğretmen';
    }
}
