<?php

namespace App\Http\Controllers;

use App\Models\DietType;
use App\Models\Allergy;

class PreferenceController extends Controller
{
    public function diets()
    {
        return DietType::pluck('name');
    }

    public function allergies()
    {
        return Allergy::pluck('name');
    }
}
