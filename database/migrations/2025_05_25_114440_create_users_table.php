<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            // ... autres champs ...
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedBigInteger('diet_type_id')->nullable();
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->unsignedBigInteger('activity_level_id')->nullable();
            $table->unsignedInteger('weight_target')->nullable();
            $table->string('custom_diet')->nullable();
            $table->string('custom_allergy')->nullable();

            $table->foreign('diet_type_id')->references('id')->on('diet_types')->nullOnDelete();
            $table->foreign('goal_id')->references('id')->on('goals')->nullOnDelete();
            $table->foreign('activity_level_id')->references('id')->on('activity_levels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
