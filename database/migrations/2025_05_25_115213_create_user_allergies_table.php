<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAllergiesTable extends Migration
{
    public function up()
    {
        Schema::create('user_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('allergy_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'allergy_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_allergies');
    }
}
