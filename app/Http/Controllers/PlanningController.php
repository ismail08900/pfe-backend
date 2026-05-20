<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planning;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class PlanningController extends Controller
{
    // Récupérer le planning de la semaine courante
    public function getCurrentWeekPlanning(Request $request)
    {
        $user = Auth::user();
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $planning = Planning::where('user_id', $user->id)
            ->where('week_start', $monday)
            ->first();

        // Si pas de planning pour cette semaine, on le crée et on le sauvegarde en base
        if (!$planning) {
            $emptyWeek = [];
            $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            foreach ($days as $day) {
                $emptyWeek[$day] = [
                    'breakfast' => null,
                    'lunch' => null,
                    'dinner' => null,
                    'snack' => null,
                ];
            }
            $planning = Planning::create([
                'user_id' => $user->id,
                'week_start' => $monday,
                'planning' => $emptyWeek,
            ]);
        }

        return response()->json([
            'week_start' => $planning->week_start->toDateString(),
            'planning' => $planning->planning,
        ]);
    }

    // Sauvegarder ou mettre à jour le planning de la semaine courante
    public function saveCurrentWeekPlanning(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'planning' => 'required|array',
            'week_start' => 'required|date'
        ]);

        $planning = Planning::updateOrCreate(
            [
                'user_id' => $user->id,
                'week_start' => $validated['week_start'],
            ],
            [
                'planning' => $validated['planning'],
            ]
        );

        return response()->json([
            'message' => 'Planning enregistré avec succès.',
            'planning' => $planning->planning,
        ]);
    }

    public function consumptions(Request $request)
    {
        $user = Auth::user();
        $weekStart = $request->input('week_start', now()->startOfWeek(Carbon::MONDAY)->toDateString());

        $planning = Planning::where('user_id', $user->id)
            ->where('week_start', $weekStart)
            ->first();

        $daysOfWeek = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $daysTotals = [];
        foreach ($daysOfWeek as $day) {
            $daysTotals[$day] = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
        }

        $weekTotals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];

        if ($planning) {
            $planningData = $planning->planning ?? [];
            foreach ($planningData as $day => $meals) {
                if (in_array($day, $daysOfWeek) && is_array($meals)) {
                    foreach ($meals as $meal) {
                        if (is_array($meal)) {
                            $daysTotals[$day]['calories'] += $meal['calories'] ?? 0;
                            $daysTotals[$day]['protein']  += $meal['protein'] ?? 0;
                            $daysTotals[$day]['carbs']    += $meal['carbs'] ?? 0;
                            $daysTotals[$day]['fat']      += $meal['fat'] ?? 0;
                        }
                    }
                }
            }

            foreach ($daysTotals as $totals) {
                $weekTotals['calories'] += $totals['calories'];
                $weekTotals['protein']  += $totals['protein'];
                $weekTotals['carbs']    += $totals['carbs'];
                $weekTotals['fat']      += $totals['fat'];
            }
        }

        return response()->json([
            'week_start'  => $planning ? $planning->week_start->toDateString() : $weekStart,
            'days'        => $daysTotals,
            'week_totals' => $weekTotals,
        ]);
    }

    public function weeklyConsumptions(Request $request)
    {
        // Alias pour la méthode consumptions pour la compatibilité
        return $this->consumptions($request);
    }

    public function monthlyConsumptions(Request $request)
    {
        $user = Auth::user();

        // Permettre de passer le mois en paramètre (format '2025-06'), sinon mois courant
        $month = $request->input('month', now()->format('Y-m'));

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // Récupérer tous les plannings de l'utilisateur pour ce mois
        $plannings = Planning::where('user_id', $user->id)
            ->whereBetween('week_start', [$start, $end])
            ->get();

        // Index pour retrouver le numéro du jour de la semaine (si 'lundi', 'mardi', etc.)
        $daysOfWeek = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

        $dailyTotals = []; // indexé par date
        $monthTotals = [
            'calories' => 0,
            'protein'  => 0,
            'carbs'    => 0,
            'fat'      => 0,
        ];

        foreach ($plannings as $planning) {
            $planningData = $planning->planning; // array (cast auto)
            foreach ($planningData as $dayLabel => $meals) {
                // Calculer la vraie date du jour de la semaine
                $date = Carbon::parse($planning->week_start)->addDays(
                    array_search($dayLabel, $daysOfWeek)
                )->toDateString();

                // On ne garde que les jours du mois (si un planning chevauche sur 2 mois)
                if ($date < $start->toDateString() || $date > $end->toDateString()) {
                    continue;
                }

                if (!isset($dailyTotals[$date])) {
                    $dailyTotals[$date] = [
                        'calories' => 0,
                        'protein'  => 0,
                        'carbs'    => 0,
                        'fat'      => 0,
                        'meals'    => $meals, // on peut ajouter le détail des repas du jour
                    ];
                }

                foreach ($meals as $meal) {
                    $dailyTotals[$date]['calories'] += $meal['calories'] ?? 0;
                    $dailyTotals[$date]['protein']  += $meal['protein'] ?? 0;
                    $dailyTotals[$date]['carbs']    += $meal['carbs'] ?? 0;
                    $dailyTotals[$date]['fat']      += $meal['fat'] ?? 0;
                }
            }
        }

        // Calcul des totaux du mois
        foreach ($dailyTotals as $day => $totals) {
            $monthTotals['calories'] += $totals['calories'];
            $monthTotals['protein']  += $totals['protein'];
            $monthTotals['carbs']    += $totals['carbs'];
            $monthTotals['fat']      += $totals['fat'];
        }

        // Tri des jours par date (optionnel mais pratique pour l'affichage)
        ksort($dailyTotals);

        return response()->json([
            'month'        => $month,
            'monthTotals'  => $monthTotals,
            'dailyTotals'  => $dailyTotals,
        ]);
    }

    public function getTodayMeals(Request $request)
    {
        try {
            $user = Auth::user();
            $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

            $planning = Planning::where('user_id', $user->id)
                ->where('week_start', $monday)
                ->first();

            // Structure par défaut pour les repas
            $defaultMeals = [
                'breakfast' => null,
                'lunch' => null,
                'dinner' => null,
                'snack' => null,
            ];

            // Si pas de planning pour cette semaine
            if (!$planning) {
                return response()->json($defaultMeals);
            }

            // Récupérer et valider les données du planning
            $planningData = $planning->planning;

            // Si ce n'est pas déjà un tableau, décode le JSON
            if (is_string($planningData)) {
                $planningData = json_decode($planningData, true);

                // Vérifier si le décodage JSON a échoué
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'error' => 'Erreur de format des données de planning',
                        'meals' => $defaultMeals
                    ], 500);
                }
            }

            // Obtenir le jour actuel en français
            $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            $todayKey = $jours[Carbon::now()->dayOfWeek];

            // Récupérer les repas du jour avec une structure par défaut
            $todayMeals = $planningData[$todayKey] ?? $defaultMeals;

            // S'assurer que tous les repas ont la structure attendue
            foreach ($defaultMeals as $mealType => $defaultValue) {
                if (!isset($todayMeals[$mealType])) {
                    $todayMeals[$mealType] = $defaultValue;
                } else {
                    // Valider et nettoyer les données de chaque repas
                    $meal = $todayMeals[$mealType];
                    if ($meal !== null) {
                        $todayMeals[$mealType] = [
                            'title' => $meal['title'] ?? 'Sans titre',
                            'calories' => (float)($meal['calories'] ?? 0),
                            'protein' => (float)($meal['protein'] ?? 0),
                            'carbs' => (float)($meal['carbs'] ?? 0),
                            'fat' => (float)($meal['fat'] ?? 0),
                            'image' => $meal['image'] ?? null,
                        ];
                    }
                }
            }

            return response()->json($todayMeals);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération des repas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
