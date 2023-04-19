<?php

namespace App\Services;

use App\Models\Client;

class ClientService
{
    public static function addProject(array $validatedAddClient): bool
    {
        $log = Client::create($validatedAddClient);

        if (! is_object($log)) {
            return false;
        }

        return true;
    }
}
