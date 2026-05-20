<?php

namespace App\Http\Controllers;

use App\Models\ActivityLevel;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NutritionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function myAllergies(Request $request)
    {
        // Récupère l'utilisateur connecté
        $user = $request->user(); // ou auth()->user();

        // Récupère ses allergies via la relation
        $allergies = $user->allergies()->get();

        // Retourne en JSON
        return response()->json([
            'allergies' => $allergies
        ]);
    }

    public function updateProfile(Request $request)
    {

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'height'     => 'nullable|integer',
            'weight'     => 'nullable|integer',
            'weight_target' => 'nullable|integer',
            'activity_level_id' => 'nullable|exists:activity_levels,id',
            'goal_id' => 'nullable|exists:goals,id',
            'diet_type_id' => 'nullable|exists:diet_types,id',
            'allergy_ids' => 'nullable|array',
            'allergy_ids.*' => 'exists:allergies,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        $user->height     = $request->height ?? null;
        $user->weight     = $request->weight ?? null;
        $user->weight_target = $request->weight_target ?? null;
        $user->activity_level_id = $request->activity_level_id ?? null;
        $user->goal_id = $request->goal_id ?? null;
        $user->diet_type_id = $request->diet_type_id ?? null;
        $user->save();

        // Met à jour les allergies (array d'id)
        if ($request->has('allergy_ids')) {
            $user->allergies()->sync($request->allergy_ids);
        }

        // Recharge les relations pour le retour
        $user->load('allergies', 'dietType');

        return response()->json([
            'message' => 'Profil mis à jour',
            'user' => $user
        ]);
    }

    public function tdee()
    {
        $user = Auth::user();
        // Récupérer l'objet (relation Eloquent) associé à l'utilisateur
        $activity = ActivityLevel::find($user->activity_level_id);
        $goal = Goal::find($user->goal_id);

        $tdee = NutritionService::calculateTDEE(
            $user->gender,
            $user->weight,
            $user->height,
            $user->birth_date,
            $activity->name ?? 'sedentary',
            $goal->name ?? 'weight maintenance'
        );

        return response()->json($tdee);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que le mot de passe actuel est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect'
            ], 400);
        }

        // Vérifier que le nouveau mot de passe est différent de l'actuel
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'message' => 'Le nouveau mot de passe doit être différent de l\'actuel'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'password_confirmation' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que le mot de passe est correct
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe est incorrect'
            ], 400);
        }

        // Supprimer les données associées (optionnel, selon vos besoins)
        // $user->plannings()->delete(); // Si vous voulez supprimer les plannings
        // $user->allergies()->detach(); // Détacher les allergies

        // Supprimer l'utilisateur
        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }
}
