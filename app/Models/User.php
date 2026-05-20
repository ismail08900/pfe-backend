<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// Ajoute ce use pour ta notification personnalisée
use App\Notifications\CustomVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "phone",
        "password",
        "birth_date",
        "gender",
        "height",
        "weight",
        "diet_type_id",
        "goal_id",
        "activity_level_id",
        "weight_target",
        "custom_diet",
        "custom_allergy"
    ];

    protected $hidden = ["password", "remember_token"];

    // Ajoute cette méthode pour utiliser la notification personnalisée
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    public function allergies()
    {
        return $this->belongsToMany(Allergy::class, 'user_allergies');
    }
    public function dietType()
    {
        return $this->belongsTo(DietType::class);
    }
    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }
    public function activityLevel()
    {
        return $this->belongsTo(ActivityLevel::class);
    }
}
