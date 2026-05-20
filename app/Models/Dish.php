<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'calories',
        'proteins',
        'lipids',
        'carbs',
        'type',
        'image'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function diets()
    {
        return $this->belongsToMany(DietType::class, 'dish_diet');
    }

    public function allergies()
    {
        return $this->belongsToMany(Allergy::class, 'dish_allergy');
    }
}
