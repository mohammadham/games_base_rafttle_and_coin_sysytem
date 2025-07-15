<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Attempt to use the 'core' prefix if that's how other tables are structured.
        // This needs to be verified against the existing database structure.
        // If other tables don't have a prefix, remove 'core.' from 'core.api_keys'.
        $tableName = 'api_keys'; // Default table name
        // It's safer to not assume a prefix unless explicitly known from other migrations/DB structure.
        // $prefix = env('DB_TABLE_PREFIX', ''); // Or however your prefix is defined, if any.
        // $tableName = $prefix . 'api_keys';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            // Assuming 'users' table exists and user_id should reference it.
            // Adjust if your users table has a different name or structure.
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('ID of the user/developer who owns this key');
            // Optional: If each game has its own key, link to a games table
            // $table->unsignedBigInteger('game_id')->nullable()->index();
            $table->string('name')->comment('A descriptive name for the API key, e.g., My Awesome Game Key');
            $table->string('api_key', 64)->unique()->comment('The public API key');
            $table->string('secret_key', 128)->comment('The secret key (should be hashed if shown to user only once)');
            $table->tinyInteger('status')->default(Status::ENABLE)->comment('See App\Constants\Status. 0: Inactive, 1: Active');
            $table->text('allowed_ips')->nullable()->comment('Comma-separated list of IPs allowed to use this key');
            $table->json('permissions')->nullable()->comment('JSON-encoded permissions, e.g., ["credit_coin", "debit_coin"]');
            $table->unsignedInteger('usage_count')->default(0)->comment('How many times this key has been used');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps(); // created_at and updated_at

            // Example of a foreign key constraint. Ensure 'users' table exists.
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = 'api_keys';
        // $prefix = env('DB_TABLE_PREFIX', '');
        // $tableName = $prefix . 'api_keys';
        Schema::dropIfExists($tableName);
    }
};
