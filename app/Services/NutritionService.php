<?php

namespace App\Services;

use Carbon\Carbon;

class NutritionService
{
    public static function calculateTDEE($gender, $weight, $height, $birth_date, $activity_level, $goal)
    {
        // 1. Age
        $age = Carbon::parse($birth_date)->age;

        // 2. TMB (Mifflin St-Jeor)
        $tmb = (10 * $weight) + (6.25 * $height) - (5 * $age);
        if ($gender === 'male') {
            $tmb += 5;
        } else {
            $tmb -= 161;
        }

        // 3. Facteur d'activité
        $activity_factors = [
            'sedentary' => 1.2,
            'lightly active' => 1.375,
            'moderately active' => 1.55,
            'active' => 1.725,
            'very active' => 1.9,
        ];
        $activity = $activity_factors[$activity_level] ?? 1.2;

        // 4. Calories maintenance
        $calories = $tmb * $activity;
        $maintenance_calories = round($tmb * $activity);
        // 5. Objectif
        if ($goal === 'weight loss') {
            $calories *= 0.85;
        }
        if ($goal === 'weight gain' || $goal === 'muscle gain') {
            $calories *= 1.15;
        }
        // 'maintain' => rien

        $calories = round($calories);

        // 6. Macros
        if ($goal === 'weight loss') { // g/jour
            $protein = round($weight * 2.2);
        } elseif ($goal === 'muscle gain') {
            $protein = round($weight * 2.0);
        } elseif ($goal === 'weight gain') {
            $protein = round($weight * 1.8);
        } else {
            $protein = round($weight * 1.6);
        }

        if ($goal === 'weight loss') {
            $fat_percentage = 0.30; // 30% pour la perte de poids
        } else {
            $fat_percentage = 0.25; // 25% pour les autres cas
        }
        $fat = round(($calories * $fat_percentage) / 9);
        $protein_kcal = $protein * 4;
        $fat_kcal = $fat * 9;
        $carbs = round(($calories - $protein_kcal - $fat_kcal) / 4);

        return [
            'calories' => $calories,
            'protein' => $protein,
            'fat' => $fat,
            'carbs' => $carbs,
            'goal_name' => $goal,
            'maintenance_calories' => $maintenance_calories,

        ];
    }
}
