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
        $tableName = 'api_transaction_logs';
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_key_id')->index()->comment('Foreign key to api_keys table');
            $table->string('endpoint_url');
            $table->string('method', 10)->comment('HTTP method e.g. POST, GET');
            $table->text('request_payload')->nullable()->comment('JSON if possible');
            $table->text('request_headers')->nullable()->comment('JSON if possible, store relevant headers');
            $table->text('response_payload')->nullable()->comment('JSON if possible');
            $table->integer('response_http_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_platform_id')->nullable()->index()->comment('User ID from the game/external platform');
            $table->string('game_transaction_id')->nullable()->index()->comment('Unique transaction ID from the game side');
            $table->string('platform_transaction_id')->nullable()->index()->comment('Internal TRX ID from our platform (e.g., from transactions table)');
            $table->string('coin_type')->nullable()->comment('Type of coin involved, e.g., gold, silver');
            $table->decimal('amount', 28, 8)->nullable()->comment('Amount of coins involved in the transaction');
            $table->string('action_type')->comment('e.g., credit, debit, get_balance');
            $table->tinyInteger('status')->comment('0: Failed, 1: Success');
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable()->comment('Custom error code from our API');
            $table->unsignedInteger('duration_ms')->nullable()->comment('Request processing duration in milliseconds');
            $table->timestamp('created_at')->useCurrent();

            // Consider adding foreign key constraint if api_keys table is guaranteed to exist first
            // $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('cascade');
            // onDelete('set null') might be safer if you want to keep logs even if an API key is deleted.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_transaction_logs');
    }
};
