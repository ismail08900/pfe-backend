<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        // Vérification si l'email est confirmé
        if (!$user->hasVerifiedEmail()) {
            // Génère un token temporaire pour la vérification email
            $user->sendEmailVerificationNotification();
            $token = $user->createToken('verify-email')->plainTextToken;
            return response()->json([
                'message' => "Votre adresse e-mail n'a pas encore été vérifiée. Veuillez vérifier votre boîte e-mail ou demander un nouvel envoi.",
                'email_verified' => false,
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => $token // <-- ICI
            ], 403);
        }

        $token = $user->createToken('main')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'email_verified' => true,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté']);
    }
}
