<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller (skeleton recovery, B3). The Laravel 11 default base is empty; the ICS overlay
 * controllers call `$this->authorize()` (13 call sites) and rely on validation helpers, so the base
 * composes AuthorizesRequests + ValidatesRequests (the established platform expectation, D-021/D-044).
 */
abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;
}
