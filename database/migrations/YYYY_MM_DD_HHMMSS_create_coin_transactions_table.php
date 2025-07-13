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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('User involved in the transaction');
            $table->unsignedBigInteger('coin_type_id')->index()->comment('Type of coin transacted');

            // Polymorphic relation to link with various transaction types in the system
            // This will create 'related_transactionable_id' (unsignedBigInteger) and 'related_transactionable_type' (string)
            $table->nullableMorphs('related_transactionable', 'coin_trx_related_idx');

            $table->unsignedBigInteger('game_api_key_id')->nullable()->index()->comment('If transaction originated from a game API call, references api_keys.id');

            $table->decimal('amount', 28, 8)->comment('Amount of coin transacted. Positive for credit/increase, negative for debit/decrease.');
            $table->decimal('post_balance', 28, 8)->comment('User coin balance for this specific coin_type_id after this transaction');
            $table->decimal('charge', 28, 8)->default(0)->comment('Any charge (in this coin type) associated with this coin transaction itself');

            $table->char('trx_type', 1)->comment('+ for credit, - for debit');
            $table->string('remark', 100)->index()->comment('Short remark for the transaction type, e.g., api_credit, item_purchase, admin_adjustment, initial_creation');
            $table->text('details')->nullable()->comment('More details about the transaction, can be JSON (e.g., reason for admin adjustment, item purchased)');
            $table->string('trx', 40)->unique()->comment('Unique transaction ID for this specific coin_transaction record');

            $table->unsignedBigInteger('created_by_admin_id')->nullable()->comment('Admin who initiated or adjusted this transaction, references admins.id');

            $table->timestamp('created_at')->useCurrent();
            // No `updated_at` for transaction logs as they are typically immutable.

            // Foreign key constraints (ensure related tables exist)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coin_type_id')->references('id')->on('coin_types')->onDelete('cascade');
            $table->foreign('game_api_key_id')->references('id')->on('api_keys')->onDelete('set null');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coin_transactions');
    }
};
