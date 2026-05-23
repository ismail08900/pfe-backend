<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DietType;
use App\Models\Goal;
use App\Models\ActivityLevel;
use App\Models\Allergy;

class ReferenceSeeder extends Seeder
{
    public function run()
    {
        $diets = [
            'pescetarian',
            'vegetarian',
            'lacto-vegetarian',
            'ovo-vegetarian',
            'vegan',
            'ketogenic',
            'gluten free',
            'paleo',
            'primal',
            'low FODMAP',
            'whole30',
        ];
        foreach ($diets as $diet) {
            DietType::firstOrCreate(['name' => $diet]);
        }

        $goals = [
            'weight loss',
            'weight maintenance',
            'weight gain',
            'muscle gain',
        ];
        foreach ($goals as $goal) {
            Goal::firstOrCreate(['name' => $goal]);
        }

        $activities = [
            'sedentary',
            'lightly active',
            'moderately active',
            'active',
            'very active',
        ];
        foreach ($activities as $activity) {
            ActivityLevel::firstOrCreate(['name' => $activity]);
        }

        $allergies = [
            'dairy',
            'egg',
            'gluten',
            'grain',
            'peanut',
            'seafood',
            'sesame',
            'shellfish',
            'soy',
            'sulfite',
            'wheat',
            'tree nut',
        ];
        foreach ($allergies as $allergy) {
            Allergy::firstOrCreate(['name' => $allergy]);
        }
    }
}
