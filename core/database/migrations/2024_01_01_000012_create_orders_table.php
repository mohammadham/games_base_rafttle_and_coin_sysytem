<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\Status; // Assuming you use this for order statuses

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('order_number', 40)->unique()->comment('Unique, user-friendly order identifier');
            $table->string('trx', 40)->nullable()->index()->comment('Transaction ID from payment gateway or coin transaction');

            $table->decimal('total_amount_base_coin', 28, 8)->comment('Total order amount in base coin value');
            $table->unsignedInteger('item_count')->comment('Total number of items in the order');

            // Consider a more detailed status system if needed (e.g., paid, unpaid, processing, shipped, delivered, cancelled, refunded)
            // Using ViserGo's Status constants if applicable, or define new ones.
            $table->tinyInteger('status')->default(Status::ORDER_PENDING)->comment('Order status, e.g., 0:Pending, 1:Paid/Processing, 2:Completed, 3:Cancelled, 4:Refunded');

            $table->string('payment_method', 50)->nullable()->comment('e.g., coin_payment, gateway_zarinpal, etc.');
            $table->string('payment_via', 100)->nullable()->comment('Specific coin code used, or gateway name/trx_id'); // e.g. MAIN_COIN, ZARINPAL

            $table->json('shipping_address')->nullable()->comment('Can store shipping address as JSON or use a separate address table and FK');
            $table->text('customer_note')->nullable()->comment('Optional note from customer');
            $table->text('admin_note')->nullable()->comment('Optional note from admin');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps(); // created_at, updated_at

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // You might want to add an index on status for faster querying of orders by status.
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
