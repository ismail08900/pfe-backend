<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function chatResponse(array $history, string $message): string
    {
        // Format history for Gemini
        $contents = [];
        
        $systemContext = "Tu es un diététicien expert et bienveillant pour l'application Eatwise. Tu aides les utilisateurs à manger sainement.
IMPORTANT: 
À chaque fois que tu proposes un ou plusieurs repas (que ce soit pour un seul repas ou toute une journée), tu DOIS inclure à la TOUTE FIN de ton message un bloc JSON valide (entouré de ```json et ```) contenant la liste des repas que tu viens de proposer. 
Chaque repas doit indiquer le jour (lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche) et le type de repas (breakfast, lunch, dinner, snack). 
Si l'utilisateur ne précise pas le jour, choisis par défaut 'lundi' ou le jour pertinent.

Exemple de bloc JSON attendu à la fin de ton message :
```json
[
  {
    \"title\": \"Blanc de poulet et quinoa\",
    \"day\": \"lundi\",
    \"meal\": \"lunch\",
    \"calories\": 450,
    \"protein\": 35,
    \"carbs\": 45,
    \"fat\": 15
  },
  {
    \"title\": \"Pavé de saumon\",
    \"day\": \"lundi\",
    \"meal\": \"dinner\",
    \"calories\": 500,
    \"protein\": 30,
    \"carbs\": 20,
    \"fat\": 25
  }
]
```
Ne mets rien d'autre après ce bloc JSON.";

        // For Gemini, alternating user and model messages
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemContext]]
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => 'Compris. Je suis prêt à t\'aider !']]
        ];

        // Merge consecutive roles to satisfy Gemini's strict alternating roles requirement
        foreach ($history as $msg) {
            $role = $msg['sender'] === 'user' ? 'user' : 'model';
            $text = $msg['text'];
            
            if (empty($text)) {
                continue;
            }

            if (!empty($contents) && $contents[count($contents) - 1]['role'] === $role) {
                // Append text to the previous message of the same role
                $contents[count($contents) - 1]['parts'][0]['text'] .= "\n\n" . $text;
            } else {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
            }
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
        
        if (preg_match('/```(?:json)?\s*(\{.*\}|\[.*\])\s*```/s', $responseJsonString, $matches)) {
            $responseJsonString = $matches[1];
        } elseif (preg_match('/(\{.*\}|\[.*\])/s', $responseJsonString, $matches)) {
            $responseJsonString = $matches[0];
        }
        
        $decoded = json_decode($responseJsonString, true);
        if (!$decoded) {
            Log::error("Erreur de décodage JSON de Gemini : " . $responseJsonString);
            return [
                'error' => 'Erreur lors de la génération de la recette par l\'IA.',
                'raw' => $responseJsonString
            ];
        }
        
        // Générer un ID unique temporaire pour la recette IA
        $recipeId = 'ai_' . uniqid();
        $decoded['id'] = $recipeId;
        $decoded['image'] = 'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&q=80&w=800'; // Image générique par défaut

        // Cache la recette pendant 24 heures pour pouvoir la consulter via l'ID
        \Illuminate\Support\Facades\Cache::put('ai_recipe_' . $recipeId, $decoded, now()->addDay());

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
        if (preg_match('/```(?:json)?\s*(\{.*\}|\[.*\])\s*```/s', $responseJsonString, $matches)) {
            $responseJsonString = $matches[1];
        } elseif (preg_match('/(\{.*\}|\[.*\])/s', $responseJsonString, $matches)) {
            $responseJsonString = $matches[0];
        }
        
        $decoded = json_decode($responseJsonString, true);
        if (!$decoded) {
            Log::error("Erreur JSON Gemini Planning : " . $responseJsonString);
            return ['error' => 'Erreur lors de la génération du planning'];
        }

        return $decoded;
    }

    protected function callGemini(array $contents): string
    {
        try {
            $response = Http::timeout(120)->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                ]
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }

            if ($response->status() === 429) {
                return "Je reçois un peu trop de messages en ce moment. Veuillez patienter une petite minute avant de me reparler !";
            }

            Log::error("Erreur API Gemini", ['response' => $response->body()]);
            return "Désolé, je rencontre une erreur de connexion à l'intelligence artificielle.";
        } catch (\Exception $e) {
            Log::error("Exception API Gemini: " . $e->getMessage());
            return "Désolé, je rencontre une erreur de connexion à l'intelligence artificielle.";
        }
    }
}
