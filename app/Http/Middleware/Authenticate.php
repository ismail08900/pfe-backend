<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    // app/Http/Middleware/Authenticate.php

    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return env('FRONTEND_URL', '/');
        }
    }
}
