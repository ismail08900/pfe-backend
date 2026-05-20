<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request)
    {
        $admin = $request->user();
        return response()->json([
            'admin' => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ]
        ]);
    }

    public function users()
    {
        $users = \App\Models\User::all();
        return response()->json(['users' => $users]);
    }

    public function deleteUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    public function restaurants()
    {
        $restaurants = \App\Models\Restaurant::all();

        // Ajouter le nombre de plats pour chaque restaurant
        foreach ($restaurants as $restaurant) {
            $restaurant->dishes_count = \App\Models\Dish::where('restaurant_id', $restaurant->id)->count();
        }

        return response()->json(['restaurants' => $restaurants]);
    }

    public function deleteRestaurant($id)
    {
        $restaurant = \App\Models\Restaurant::findOrFail($id);
        $restaurant->delete();
        return response()->json(['message' => 'Restaurant supprimé']);
    }

    public function stats()
    {
        $users = \App\Models\User::count();
        $restaurants = \App\Models\Restaurant::count();
        $dishes = \App\Models\Dish::count();
        return response()->json([
            'users' => $users,
            'restaurants' => $restaurants,
            'dishes' => $dishes
        ]);
    }
}
