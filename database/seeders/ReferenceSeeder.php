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
        DietType::insert([
            ['name' => 'pescetarian'],
            ['name' => 'vegetarian'],
            ['name' => 'lacto-vegetarian'],
            ['name' => 'ovo-vegetarian'],
            ['name' => 'vegan'],
            ['name' => 'ketogenic'],
            ['name' => 'gluten free'],
            ['name' => 'paleo'],
            ['name' => 'primal'],
            ['name' => 'low FODMAP'],
            ['name' => 'whole30'],


        ]);
        Goal::insert([
            ['name' => 'weight loss'],
            ['name' => 'weight maintenance'],
            ['name' => 'weight gain'],
            ['name' => 'muscle gain'],
        ]);
        ActivityLevel::insert([
            ['name' => 'sedentary'],
            ['name' => 'lightly active'],
            ['name' => 'moderately active'],
            ['name' => 'active'],
            ['name' => 'very active'],
        ]);
        Allergy::insert([
            ['name' => 'dairy'],
            ['name' => 'egg'],
            ['name' => 'gluten'],
            ['name' => 'grain'],
            ['name' => 'peanut'],
            ['name' => 'seafood'],
            ['name' => 'sesame'],
            ['name' => 'shellfish'],
            ['name' => 'soy'],
            ['name' => 'sulfite'],
            ['name' => 'wheat'],
            ['name' => 'tree nut'],







        ]);
    }
}
