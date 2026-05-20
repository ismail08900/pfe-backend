<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function chatResponse(array $history, string $message): string
    {
        // Format history for Gemini
        $contents = [];
        
        // System instruction (we simulate this by prepending a system message if needed, or just context in the prompt)
        $systemContext = "Tu es un diététicien expert et bienveillant pour l'application Eatwise. Tu aides les utilisateurs à manger sainement, réponds à leurs questions sur la nutrition, et donnes des conseils personnalisés. Sois concis et utilise des émojis.";

        // For Gemini, alternating user and model messages
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemContext]]
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => 'Compris. Je suis prêt à t\'aider !']]
        ];

        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['text']]]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        return $this->callGemini($contents);
    }

    public function generateRecipe(array $ingredients, string $diet = '', string $allergies = ''): array
    {
        $prompt = "En tant que chef expert en nutrition, crée une recette détaillée en français utilisant au maximum les ingrédients suivants : " . implode(', ', $ingredients) . ". ";
        if ($diet) {
            $prompt .= "La recette doit respecter le régime : $diet. ";
        }
        if ($allergies) {
            $prompt .= "La recette doit exclure ces allergènes : $allergies. ";
        }
        
        $prompt .= "Retourne la réponse UNIQUEMENT au format JSON avec cette structure exacte : 
        {
            \"title\": \"Nom de la recette\",
            \"summary\": \"Brève description\",
            \"readyInMinutes\": 30,
            \"servings\": 2,
            \"calories\": 450,
            \"protein\": 25,
            \"carbs\": 40,
            \"fat\": 15,
            \"extendedIngredients\": [
                { \"original\": \"2 tomates\" }
            ],
            \"analyzedInstructions\": [
                {
                    \"steps\": [
                        { \"step\": \"Couper les tomates.\" }
                    ]
                }
            ]
        }";

        $contents = [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ]
        ];

        $responseJsonString = $this->callGemini($contents);
        
        // Nettoyer la réponse si elle contient des backticks markdown (```json ... ```)
        $responseJsonString = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseJsonString);
        
        $decoded = json_decode($responseJsonString, true);
        if (!$decoded) {
            Log::error("Erreur de décodage JSON de Gemini : " . $responseJsonString);
            return [
                'error' => 'Erreur lors de la génération de la recette par l\'IA.',
                'raw' => $responseJsonString
            ];
        }
        
        // Générer un ID unique temporaire pour la recette IA
        $decoded['id'] = 'ai_' . uniqid();
        $decoded['image'] = 'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&q=80&w=800'; // Image générique par défaut

        return $decoded;
    }

    public function generateMealPlan(array $userProfile, array $currentPlanning = [], array $siteRecipes = []): array
    {
        $prompt = "En tant que nutritionniste, crée un plan de repas hebdomadaire (Lundi à Dimanche) pour ce profil :
        Objectif : {$userProfile['goal']}
        Régime : {$userProfile['diet']}
        Allergies : {$userProfile['allergies']}
        Niveau d'activité : {$userProfile['activityLevel']}
        
        IMPORTANT: Voici le planning actuel de l'utilisateur : " . json_encode($currentPlanning) . "
        Tu dois ABSOLUMENT CONSERVER les repas (y compris les repas personnalisés ou données du site) qui sont déjà présents dans ce planning actuel pour chaque jour et chaque type de repas. Ne les écrase pas. Ton rôle est uniquement de COMPLÉTER les repas manquants (valeur null ou vide) pour avoir une semaine complète.

        TRES IMPORTANT: Pour générer de NOUVEAUX repas, tu DOIS piocher UNIQUEMENT dans cette liste de vraies recettes existantes : " . json_encode($siteRecipes) . "
        N'invente aucune autre recette. Utilise l'ID, le titre, l'image, et le temps de préparation exacts fournis dans la liste pour remplir les cases vides.

        Retourne la réponse UNIQUEMENT au format JSON avec cette structure exacte :
        {
            \"week\": {
                \"lundi\": {
                    \"meals\": [
                        { \"id\": \"ai_1\", \"title\": \"Porridge\", \"readyInMinutes\": 10, \"servings\": 1, \"sourceUrl\": \"\", \"image\": \"...\" },
                        { \"id\": \"ai_2\", \"title\": \"Salade\", \"readyInMinutes\": 15, \"servings\": 1, \"sourceUrl\": \"\", \"image\": \"...\" },
                        { \"id\": \"ai_3\", \"title\": \"Poulet rôti\", \"readyInMinutes\": 45, \"servings\": 1, \"sourceUrl\": \"\", \"image\": \"...\" },
                        { \"id\": \"ai_4\", \"title\": \"Collation\", \"readyInMinutes\": 5, \"servings\": 1, \"sourceUrl\": \"\", \"image\": \"...\" }
                    ],
                    \"nutrients\": { \"calories\": 2000, \"protein\": 100, \"fat\": 60, \"carbohydrates\": 250 }
                },
                \"mardi\": { ... }
            }
        }";

        $contents = [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ]
        ];

        $responseJsonString = $this->callGemini($contents);
        $responseJsonString = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $responseJsonString);
        
        $decoded = json_decode($responseJsonString, true);
        if (!$decoded) {
            Log::error("Erreur JSON Gemini Planning : " . $responseJsonString);
            return ['error' => 'Erreur lors de la génération du planning'];
        }

        return $decoded;
    }

    protected function callGemini(array $contents): string
    {
        $response = Http::timeout(120)->post($this->baseUrl . '?key=' . $this->apiKey, [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        Log::error("Erreur API Gemini", ['response' => $response->body()]);
        return "Désolé, je rencontre une erreur de connexion à l'intelligence artificielle.";
    }
}
