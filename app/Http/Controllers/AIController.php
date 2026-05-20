<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use App\Services\WhatsAppService;

class AIController extends Controller
{
    protected $aiService;
    protected $whatsappService;

    public function __construct(AIService $aiService, WhatsAppService $whatsappService)
    {
        $this->aiService = $aiService;
        $this->whatsappService = $whatsappService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array'
        ]);

        $history = $request->input('history', []);
        $message = $request->input('message');

        $reply = $this->aiService->chatResponse($history, $message);

        return response()->json([
            'reply' => $reply
        ]);
    }

    public function generateRecipe(Request $request)
    {
        $request->validate([
            'ingredients' => 'required|array',
            'diet' => 'nullable|string',
            'allergies' => 'nullable|string'
        ]);

        $ingredients = $request->input('ingredients');
        $diet = $request->input('diet', '');
        $allergies = $request->input('allergies', '');

        $recipe = $this->aiService->generateRecipe($ingredients, $diet, $allergies);

        if (isset($recipe['error'])) {
            return response()->json($recipe, 500);
        }

        return response()->json([
            'results' => [$recipe] // Format similaire à Spoonacular pour faciliter l'intégration côté front
        ]);
    }

    public function generatePlanning(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            // Pour les tests sans auth si besoin, ou utiliser un mock
            $userProfile = [
                'goal' => $request->input('goal', 'Manger équilibré'),
                'diet' => $request->input('diet', 'Standard'),
                'allergies' => $request->input('allergies', 'Aucune'),
                'activityLevel' => $request->input('activityLevel', 'Sédentaire')
            ];
        } else {
            $userProfile = [
                'goal' => $user->goal?->name ?? 'Manger équilibré',
                'diet' => $user->dietType?->name ?? 'Standard',
                'allergies' => $user->allergies()->pluck('name')->implode(', ') ?: 'Aucune',
                'activityLevel' => $user->activityLevel?->name ?? 'Sédentaire'
            ];
        }

        $currentPlanning = $request->input('current_planning', []);

        // Fetch real recipes from Spoonacular to give to Gemini
        $diet = $userProfile['diet'] !== 'Standard' ? $userProfile['diet'] : '';
        $allergies = $userProfile['allergies'] !== 'Aucune' ? $userProfile['allergies'] : '';
        
        $params = [
            'apiKey' => env('SPOONACULAR_API_KEY'),
            'number' => 20, // Fetch enough recipes for a week
            'addRecipeNutrition' => 'true',
            'type' => 'main course,breakfast,snack',
        ];
        if ($diet) $params['diet'] = $diet;
        if ($allergies) $params['intolerances'] = $allergies;

        $response = \Illuminate\Support\Facades\Http::get('https://api.spoonacular.com/recipes/complexSearch', $params);
        $siteRecipes = [];
        
        if ($response->successful()) {
            $recipes = $response->json()['results'] ?? [];
            foreach ($recipes as $r) {
                $siteRecipes[] = [
                    'id' => $r['id'],
                    'title' => $r['title'],
                    'image' => $r['image'] ?? '',
                    'readyInMinutes' => $r['readyInMinutes'] ?? 30,
                    'calories' => $r['nutrition']['nutrients'][0]['amount'] ?? 0 // Just an approximation for the AI
                ];
            }
        }

        $planning = $this->aiService->generateMealPlan($userProfile, $currentPlanning, $siteRecipes);

        if (isset($planning['error'])) {
            return response()->json($planning, 500);
        }

        return response()->json($planning);
    }

    public function sendPlanningWhatsApp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'planning' => 'required|array'
        ]);

        $phone = $request->input('phone');
        $planning = $request->input('planning');

        $success = $this->whatsappService->sendPlanning($phone, $planning);

        if ($success) {
            return response()->json(['message' => 'Planning envoyé avec succès sur WhatsApp !']);
        }

        return response()->json(['error' => 'Échec de l\'envoi du planning. Vérifiez la configuration Twilio.'], 500);
    }
}
