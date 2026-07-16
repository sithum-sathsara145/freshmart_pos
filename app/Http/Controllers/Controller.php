<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 11+ ships a bare base controller; this brings back $this->authorize()
    // so controllers can defer to the policies in app/Policies.
    use AuthorizesRequests;
}
