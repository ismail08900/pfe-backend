<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        // Autorise les routes de vérification même pour email non vérifié
        $allowed = [
            'api/email/verification-notification',
            'api/email/is-verified',
            // Ajoute ici d'autres routes publiques si besoin
        ];
        $path = $request->path();

        // Pour Laravel, $request->is() supporte les wildcards
        foreach ($allowed as $allow) {
            if ($request->is($allow)) {
                return $next($request);
            }
        }

        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Vous devez vérifier votre adresse email.'], 403);
        }

        return $next($request);
    }
}
