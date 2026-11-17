<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait WithUser
{
    protected function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
