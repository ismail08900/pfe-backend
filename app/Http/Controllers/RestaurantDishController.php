<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Diet;
use App\Models\Allergy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class RestaurantDishController extends Controller
{
    // Lister tous les plats du restaurant connecté
    public function index(Request $request)
    {
        $restaurant = $request->user();
        $dishes = Dish::with(['diets', 'allergies'])
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('created_at')
            ->get();
        return response()->json($dishes);
    }

    // Ajouter un plat
    public function store(Request $request)
    {
        $restaurant = $request->user();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|integer|min:0',
            'calories'    => 'required|integer|min:0',
            'proteins'    => 'required|numeric|min:0',
            'lipids'      => 'required|numeric|min:0',
            'carbs'       => 'required|numeric|min:0',
            'type'        => 'required|in:petit dejeuner,dejeuner,diner,collation',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'diets'       => 'required|array',
            'diets.*'     => 'exists:diet_types,id',
            'allergies'   => 'required|array',
            'allergies.*' => 'exists:allergies,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        // Sauvegarde de l'image
        $imagePath = $request->file('image')->store('dishes', 'public');

        $dish = Dish::create([
            'restaurant_id' => $restaurant->id,
            'name'          => $request->name,
            'description'   => $request->description,
            'price'         => $request->price,
            'calories'      => $request->calories,
            'proteins'      => $request->proteins,
            'lipids'        => $request->lipids,
            'carbs'         => $request->carbs,
            'type'          => $request->type,
            'image'         => $imagePath,
        ]);

        $dish->diets()->sync($request->diets);
        $dish->allergies()->sync($request->allergies);

        return response()->json($dish->load(['diets', 'allergies']), 201);
    }

    // Modifier un plat
    public function update(Request $request, $id)
    {
        $restaurant = $request->user();
        $dish = Dish::where('id', $id)->where('restaurant_id', $restaurant->id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price'       => 'sometimes|required|integer|min:0',
            'calories'    => 'sometimes|required|integer|min:0',
            'proteins'    => 'sometimes|required|numeric|min:0',
            'lipids'      => 'sometimes|required|numeric|min:0',
            'carbs'       => 'sometimes|required|numeric|min:0',
            'type'        => 'sometimes|required|in:petit dejeuner,dejeuner,diner,collation',
            'image'       => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'diets'       => 'sometimes|required|array',
            'diets.*'     => 'exists:diet_types,id',
            'allergies'   => 'sometimes|required|array',
            'allergies.*' => 'exists:allergies,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        // Si nouvelle image, supprimer l’ancienne
        if ($request->hasFile('image')) {
            if ($dish->image) {
                Storage::disk('public')->delete($dish->image);
            }
            $dish->image = $request->file('image')->store('dishes', 'public');
        }

        $dish->fill($request->except(['image', 'diets', 'allergies']));
        $dish->save();

        if ($request->has('diets')) {
            $dish->diets()->sync($request->diets);
        }
        if ($request->has('allergies')) {
            $dish->allergies()->sync($request->allergies);
        }

        return response()->json($dish->load(['diets', 'allergies']));
    }

    // Supprimer un plat
    public function destroy(Request $request, $id)
    {
        $restaurant = $request->user();
        $dish = Dish::where('id', $id)->where('restaurant_id', $restaurant->id)->firstOrFail();

        // Supprimer image
        if ($dish->image) {
            Storage::disk('public')->delete($dish->image);
        }

        $dish->delete();

        return response()->json(['message' => 'Plat supprimé avec succès']);
    }
}
