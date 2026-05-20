<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // Renvoie l'email de vérification
    public function send(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 409);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Email de vérification envoyé.']);
    }

    // Vérifie si l'email est déjà vérifié
    public function isVerified(Request $request)
    {
        $user = $request->user();
        return response()->json(['verified' => $user->hasVerifiedEmail()]);
    }
}
