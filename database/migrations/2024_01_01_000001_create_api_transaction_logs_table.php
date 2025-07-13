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
        Schema::create('api_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_key_id');
            $table->string('endpoint');
            $table->text('request_data');
            $table->text('response_data');
            $table->string('ip_address');
            $table->timestamps();

            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_transaction_logs');
    }
};
