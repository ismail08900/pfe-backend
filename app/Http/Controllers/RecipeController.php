<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\LibreTranslateService;

// class RecipeController extends Controller
// {
//     protected $translator;

//     public function __construct(LibreTranslateService $translator)
//     {
//         $this->translator = $translator;
//     }

//     public function getUserRecipes(Request $request)
//     {
//         $user = $request->user();

//         $diet = $user->dietType?->name ?? null;
//         $allergies = $user->allergies()->pluck('name')->toArray();
//         $intolerances = $allergies ? implode(',', $allergies) : null;

//         $includeIngredients = $request->input('includeIngredients');
//         $excludeIngredients = $request->input('excludeIngredients');
//         $cuisine = $request->input('cuisine');
//         $type = $request->input('type');
//         $maxReadyTime = $request->input('maxReadyTime', $request->input('maxTime'));
//         $maxCalories = $request->input('maxCalories');
//         $minCalories = $request->input('minCalories');
//         $maxProtein = $request->input('maxProtein');
//         $minProtein = $request->input('minProtein');
//         $maxCarbs   = $request->input('maxCarbs');
//         $minCarbs   = $request->input('minCarbs');
//         $maxFat     = $request->input('maxFat');
//         $minFat     = $request->input('minFat');
//         $minServings = $request->input('minServings');


//         $params = [
//             'apiKey' => env('SPOONACULAR_API_KEY'),
//             'number' => 9,
//             'addRecipeNutrition' => 'true',
//             'excludeIngredients' => 'pork,bacon,ham,alcohol,wine,beer,rum,gelatin,lard,prosciutto,chorizo,pepperoni,sausage',
//         ];
//         if ($diet) $params['diet'] = $diet;
//         if ($intolerances) $params['intolerances'] = $intolerances;
//         if (!empty($includeIngredients)) $params['includeIngredients'] = $includeIngredients;
//         if (!empty($excludeIngredients)) $params['excludeIngredients'] .= ',' . $excludeIngredients;
//         if (!empty($cuisine)) $params['cuisine'] = $cuisine;
//         if (!empty($type)) $params['type'] = $type;
//         if (!empty($maxReadyTime)) $params['maxReadyTime'] = $maxReadyTime;
//         if (!empty($minCalories)) $params['minCalories'] = $minCalories;
//         if (!empty($maxCalories)) $params['maxCalories'] = $maxCalories;
//         if (!empty($minProtein))  $params['minProtein'] = $minProtein;
//         if (!empty($maxProtein))  $params['maxProtein'] = $maxProtein;
//         if (!empty($minCarbs))    $params['minCarbs'] = $minCarbs;
//         if (!empty($maxCarbs))    $params['maxCarbs'] = $maxCarbs;
//         if (!empty($minFat))      $params['minFat'] = $minFat;
//         if (!empty($maxFat))      $params['maxFat'] = $maxFat;
//         if (!empty($minServings)) $params['minServings'] = $minServings;

//         $response = Http::get('https://api.spoonacular.com/recipes/complexSearch', $params);

//         if (!$response->successful()) {
//             return response()->json(['error' => 'Erreur avec Spoonacular'], 500);
//         }

//         $recipes = $response->json()['results'] ?? [];
//         $detailedRecipes = [];

//         foreach ($recipes as $recipe) {

//             // Traduction individuelle
//             $translatedTitle = $this->translator->translate($recipe['title'] ?? '');
//             $translatedSummary = $this->translator->translate(isset($recipe['summary']) ? strip_tags($recipe['summary']) : '');

//             $translatedDiets = [];
//             foreach (($recipe['diets'] ?? []) as $diet) {
//                 $translatedDiets[] = $this->translator->translate($diet);
//             }
//             $translatedDishTypes = [];
//             foreach (($recipe['dishTypes'] ?? []) as $dish) {
//                 $translatedDishTypes[] = $this->translator->translate($dish);
//             }
//             // Ajoute ici la traduction pour tags si besoin

//             $detailedRecipes[] = [
//                 'id' => $recipe['id'] ?? null,
//                 'title' => $translatedTitle,
//                 'image' => $recipe['image'] ?? '',
//                 'readyInMinutes' => $recipe['readyInMinutes'] ?? 30,
//                 'servings' => $recipe['servings'] ?? 1,
//                 'sourceUrl' => $recipe['sourceUrl'] ?? '',
//                 'calories' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Calories') : '—',
//                 'protein' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Protein') : '—',
//                 'fat' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Fat') : '—',
//                 'carbs' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Carbohydrates') : '—',
//                 'summary' => $translatedSummary,
//                 'diets' => $translatedDiets,
//                 'dishTypes' => $translatedDishTypes,
//             ];
//         }

//         return response()->json([
//             'results' => $detailedRecipes
//         ]);
//     }

//     private static function getNutrient($nutrients, $name)
//     {
//         foreach ($nutrients as $nutrient) {
//             if (strcasecmp($nutrient['name'], $name) === 0) {
//                 return round($nutrient['amount']);
//             }
//         }
//         return '—';
//     }

//     public function getRecipeDetails($id)
//     {
//         $params = [
//             'apiKey' => env('SPOONACULAR_API_KEY'),
//             'includeNutrition' => 'true',
//         ];

//         $response = Http::get("https://api.spoonacular.com/recipes/{$id}/information", $params);

//         if (!$response->successful()) {
//             return response()->json(['error' => 'Erreur lors de la récupération des détails'], 500);
//         }

//         $recipe = $response->json();

//         if (!empty($recipe['title'])) {
//             $recipe['title'] = $this->translator->translate($recipe['title']);
//         }

//         // Traduction du résumé (summary)
//         if (!empty($recipe['summary'])) {
//             $summaryPlain = strip_tags($recipe['summary']); // enlever les balises HTML
//             $recipe['summary'] = $this->translator->translate($summaryPlain);
//         }
//         foreach (($recipe['diets'] ?? []) as $i => $diet) {
//             $recipe['diets'][$i] = $this->translator->translate($diet);
//         }

//         foreach (($recipe['dishTypes'] ?? []) as $i => $dishType) {
//             $recipe['dishTypes'][$i] = $this->translator->translate($dishType);
//         }

//         // Traduction des étapes (analyzedInstructions)
//         if (!empty($recipe['analyzedInstructions'])) {
//             foreach ($recipe['analyzedInstructions'] as $blockIdx => $block) {
//                 if (!empty($block['steps'])) {
//                     foreach ($block['steps'] as $stepIdx => $step) {
//                         if (!empty($step['step'])) {
//                             $recipe['analyzedInstructions'][$blockIdx]['steps'][$stepIdx]['step'] = $this->translator->translate($step['step']);
//                         }
//                     }
//                 }
//             }
//         }
//         // Traduction des ingrédients (extendedIngredients)
//         if (!empty($recipe['extendedIngredients'])) {
//             foreach ($recipe['extendedIngredients'] as $index => $ingredient) {
//                 if (!empty($ingredient['original'])) {
//                     $recipe['extendedIngredients'][$index]['original'] = $this->translator->translate($ingredient['original']);
//                 }
//             }
//         }



//         return response()->json($recipe);
//     }
// }

class RecipeController extends Controller
{
    public function getUserRecipes(Request $request)
    {
        $user = $request->user();

        $diet = $user->dietType?->name ?? null;
        $allergies = $user->allergies()->pluck('name')->toArray();
        $intolerances = $allergies ? implode(',', $allergies) : null;

        $includeIngredients = $request->input('includeIngredients');
        $excludeIngredients = $request->input('excludeIngredients');
        $cuisine = $request->input('cuisine');
        $type = $request->input('type');
        $maxReadyTime = $request->input('maxReadyTime', $request->input('maxTime'));
        $maxCalories = $request->input('maxCalories');
        $minCalories = $request->input('minCalories');
        $maxProtein = $request->input('maxProtein');
        $minProtein = $request->input('minProtein');
        $maxCarbs   = $request->input('maxCarbs');
        $minCarbs   = $request->input('minCarbs');
        $maxFat     = $request->input('maxFat');
        $minFat     = $request->input('minFat');
        $minServings = $request->input('minServings');

        $params = [
            'apiKey' => env('SPOONACULAR_API_KEY'),
            'number' => 9,
            'addRecipeNutrition' => 'true',
            'excludeIngredients' => 'pork,bacon,ham,alcohol,wine,beer,rum,gelatin,lard,prosciutto,chorizo,pepperoni,sausage',
        ];
        if ($diet) $params['diet'] = $diet;
        if ($intolerances) $params['intolerances'] = $intolerances;
        if (!empty($includeIngredients)) $params['includeIngredients'] = $includeIngredients;
        if (!empty($excludeIngredients)) $params['excludeIngredients'] .= ',' . $excludeIngredients;
        if (!empty($cuisine)) $params['cuisine'] = $cuisine;
        if (!empty($type)) $params['type'] = $type;
        if (!empty($maxReadyTime)) $params['maxReadyTime'] = $maxReadyTime;
        if (!empty($minCalories)) $params['minCalories'] = $minCalories;
        if (!empty($maxCalories)) $params['maxCalories'] = $maxCalories;
        if (!empty($minProtein))  $params['minProtein'] = $minProtein;
        if (!empty($maxProtein))  $params['maxProtein'] = $maxProtein;
        if (!empty($minCarbs))    $params['minCarbs'] = $minCarbs;
        if (!empty($maxCarbs))    $params['maxCarbs'] = $maxCarbs;
        if (!empty($minFat))      $params['minFat'] = $minFat;
        if (!empty($maxFat))      $params['maxFat'] = $maxFat;
        if (!empty($minServings)) $params['minServings'] = $minServings;

        $response = Http::get('https://api.spoonacular.com/recipes/complexSearch', $params);

        if (!$response->successful()) {
            return response()->json(['error' => 'Erreur avec Spoonacular'], 500);
        }

        $recipes = $response->json()['results'] ?? [];
        $detailedRecipes = [];

        foreach ($recipes as $recipe) {
            $detailedRecipes[] = [
                'id' => $recipe['id'] ?? null,
                'title' => $recipe['title'] ?? '',
                'image' => $recipe['image'] ?? '',
                'readyInMinutes' => $recipe['readyInMinutes'] ?? 30,
                'servings' => $recipe['servings'] ?? 1,
                'sourceUrl' => $recipe['sourceUrl'] ?? '',
                'calories' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Calories') : '—',
                'protein' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Protein') : '—',
                'fat' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Fat') : '—',
                'carbs' => isset($recipe['nutrition']['nutrients']) ? self::getNutrient($recipe['nutrition']['nutrients'], 'Carbohydrates') : '—',
                'summary' => isset($recipe['summary']) ? strip_tags($recipe['summary']) : '',
                'diets' => $recipe['diets'] ?? [],
                'dishTypes' => $recipe['dishTypes'] ?? [],
            ];
        }

        return response()->json([
            'results' => $detailedRecipes
        ]);
    }

    private static function getNutrient($nutrients, $name)
    {
        foreach ($nutrients as $nutrient) {
            if (strcasecmp($nutrient['name'], $name) === 0) {
                return round($nutrient['amount']);
            }
        }
        return '—';
    }

    public function getRecipeDetails($id)
    {
        // Si l'ID commence par 'ai_', on récupère la recette depuis le cache
        if (str_starts_with($id, 'ai_')) {
            $recipe = \Illuminate\Support\Facades\Cache::get('ai_recipe_' . $id);
            if ($recipe) {
                return response()->json($recipe);
            }
            return response()->json(['error' => 'Recette IA introuvable ou expirée.'], 404);
        }

        $params = [
            'apiKey' => env('SPOONACULAR_API_KEY'),
            'includeNutrition' => 'true',
        ];

        $response = Http::get("https://api.spoonacular.com/recipes/{$id}/information", $params);

        if (!$response->successful()) {
            return response()->json(['error' => 'Erreur lors de la récupération des détails'], 500);
        }

        $recipe = $response->json();

        // utiliser directement les valeurs originales
        if (!empty($recipe['summary'])) {
            $recipe['summary'] = strip_tags($recipe['summary']);
        }

        return response()->json($recipe);
    }
}
