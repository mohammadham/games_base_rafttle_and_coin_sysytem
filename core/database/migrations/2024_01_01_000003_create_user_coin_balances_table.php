<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_coin_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('coin_type_id')->index();
            // Using a high-precision decimal for balance.
            // MySQL: DECIMAL(28, 8) or DECIMAL(36, 18) for very high precision crypto-like coins
            // PostgreSQL: NUMERIC(28, 8) or NUMERIC(36, 18)
            // SQLite: TEXT (handled by Laravel's casting)
            $table->decimal('balance', 28, 8)->default(0.00000000);
            $table->timestamp('last_transaction_at')->nullable()->comment('Timestamp of the last transaction affecting this balance');
            $table->timestamps(); // created_at, updated_at

            $table->unique(['user_id', 'coin_type_id'], 'user_coin_type_unique'); // Naming the unique constraint

            // Ensure users and coin_types tables exist before creating foreign keys
            // These should match your actual table names for users and coin_types
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coin_type_id')->references('id')->on('coin_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_coin_balances');
    }
};
