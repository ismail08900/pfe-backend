<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered; // <-- à ajouter

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        if ($request->has('diet_type_id') && ($request->input('diet_type_id') === "none" || $request->input('diet_type_id') === "other")) {
            $request->merge(['diet_type_id' => null]);
        }
        $validated = $request->validate([
            "first_name" => "required|string|max:255",
            "last_name"  => "required|string|max:255",
            "email"      => "required|email|unique:users",
            "phone"      => "nullable|string|max:30",
            "password"   => "required|confirmed|min:8",
            "birth_date" => "nullable|date",
            "gender"     => "nullable|string",
            "height"     => "nullable|integer",
            "weight"     => "nullable|integer",
            "diet_type_id" => "nullable|exists:diet_types,id",
            "goal_id" => "required|exists:goals,id",
            "activity_level_id" => "required|exists:activity_levels,id",
            "weight_target" => "nullable|integer",
            "custom_diet" => "nullable|string|max:255",
            "custom_allergy" => "nullable|string|max:255",
            "allergy_ids" => "nullable|array",
            "allergy_ids.*" => "exists:allergies,id",
        ]);

        $user = User::create([
            "first_name" => $validated["first_name"],
            "last_name"  => $validated["last_name"],
            "email"      => $validated["email"],
            "phone"      => $validated["phone"] ?? null,
            "password"   => Hash::make($validated["password"]),
            "birth_date" => $validated["birth_date"] ?? null,
            "gender"     => $validated["gender"] ?? null,
            "height"     => $validated["height"] ?? null,
            "weight"     => $validated["weight"] ?? null,
            "diet_type_id" => $validated["diet_type_id"],
            "goal_id" => $validated["goal_id"],
            "activity_level_id" => $validated["activity_level_id"],
            "weight_target" => $validated["weight_target"] ?? null,
            "custom_diet" => $validated["custom_diet"] ?? null,
            "custom_allergy" => $validated["custom_allergy"] ?? null,
            "email_verified_at" => now(), // Auto-vérification pour le déploiement
        ]);

        if (!empty($validated["allergy_ids"])) {
            $user->allergies()->sync($validated["allergy_ids"]);
        }

        // Désactivation de l'envoi d'email car pas de SMTP configuré sur Railway
        // event(new Registered($user));
        $token = $user->createToken('main')->plainTextToken;

        // Connexion immédiate vu qu'on a auto-vérifié

        return response()->json([
            "message" => "Inscription réussie. Un email de vérification a été envoyé. Veuillez vérifier votre boîte de réception.",
            "token" => $token,
            "user" => $user,
        ], 201);
    }
}
