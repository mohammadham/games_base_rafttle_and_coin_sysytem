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
        Schema::create('lotteries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('competition_id')->default(0);
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->decimal('price', 28, 8)->default(0);
            $table->integer('max_buy')->default(0);
            $table->integer('segments')->default(0);
            $table->string('instant_choose_variation', 40);
            $table->string('slider_images')->nullable();
            $table->text('description')->nullable();
            $table->text('price_giving')->nullable();
            $table->dateTime('draw_date')->nullable();
            $table->integer('num_of_tickets')->default(0);
            $table->integer('num_of_available_tickets')->default(0);
            $table->integer('starting_from')->default(0);
            $table->integer('num_of_winning_tickets')->default(0);
            $table->text('winning_tickets')->nullable();
            $table->tinyInteger('status')->default(1)->comment('ENABLE = 1; DISABLE = 0; DRAWN = 2;');
            $table->boolean('is_drawn')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotteries');
    }
};
