<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Traits\ApiResponserTrait;
use App\Traits\ResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use ApiResponserTrait, AuthorizesRequests;

    protected function currentStore(): Store
    {
        /** @var Store $store */
        $store = app('currentStore');

        return $store;
    }
}
