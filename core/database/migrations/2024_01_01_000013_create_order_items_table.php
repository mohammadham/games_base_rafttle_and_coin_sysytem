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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index(); // Nullable if product can be deleted but order item remains

            $table->string('product_name_snapshot'); // Store product name at the time of purchase
            $table->string('product_image_snapshot')->nullable(); // Store main product image path at time of purchase
            // $table->string('product_sku_snapshot')->nullable(); // If you use SKUs

            $table->unsignedInteger('quantity');
            $table->decimal('price_per_unit_base_coin', 28, 8)->comment('Price per unit in base coin at time of purchase');
            $table->decimal('total_price_base_coin', 28, 8)->comment('Total price for this item (quantity * price_per_unit) in base coin');

            $table->json('attributes_snapshot')->nullable()->comment('Product attributes at time of purchase, e.g., size, color, if applicable');
            $table->timestamps(); // created_at, updated_at (updated_at might be useful if items can be modified post-order, e.g. refunded status)

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade'); // If an order is deleted, its items are deleted
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null'); // If product deleted, keep order item but nullify product_id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
