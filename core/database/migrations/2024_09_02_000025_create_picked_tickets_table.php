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
        Schema::create('picked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->default(0);
            $table->unsignedInteger('lottery_id')->default(0);
            $table->integer('quantity')->default(0);
            $table->text('choosen_tickets')->nullable();
            $table->decimal('price', 28, 8)->nullable();
            $table->unsignedInteger('deposit_id')->default(0);
            $table->tinyInteger('status')->default(0)->comment('SUCCESS = 1; PENDING = 2');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picked_tickets');
    }
};
