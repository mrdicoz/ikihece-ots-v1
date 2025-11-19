<?php

namespace App\Controllers\AI;

class VeliAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Veli';
    }
}
