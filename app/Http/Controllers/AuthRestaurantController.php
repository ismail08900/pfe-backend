<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthRestaurantController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'phone'    => 'required|string|max:20|unique:restaurants,phone',
            'email'    => 'required|email|unique:restaurants,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $restaurant = Restaurant::create([
            'name'     => $request->name,
            'location' => $request->location,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $restaurant->createToken('restaurant-token')->plainTextToken;

        return response()->json([
            'message'    => 'Restaurant registered successfully',
            'restaurant' => $restaurant,
            'token'      => $token
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $restaurant = Restaurant::where('email', $request->email)->first();

        if (!$restaurant || !Hash::check($request->password, $restaurant->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $restaurant->createToken('restaurant-token')->plainTextToken;

        return response()->json([
            'message'    => 'Login réussi',
            'restaurant' => $restaurant,
            'token'      => $token
        ]);
    }

    // (Optionnel) LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function changePassword(Request $request)
    {
        $restaurant = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $restaurant->password)) {
            return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 400);
        }

        $restaurant->password = Hash::make($request->new_password);
        $restaurant->save();

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}
