<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDishesTable extends Migration
{
    public function up()
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->integer('price'); // en DH (ex: 120 pour 120DH)
            $table->integer('calories');
            $table->float('proteins');
            $table->float('lipids');
            $table->float('carbs');
            $table->enum('type', ['petit dejeuner', 'dejeuner', 'diner', 'collation']);
            $table->string('image');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dishes');
    }
}
