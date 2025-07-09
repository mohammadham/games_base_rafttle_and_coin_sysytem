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
        Schema::create('category_product', function (Blueprint $table) {
            // Using unsignedBigInteger to match the 'id' type on products and product_categories tables.
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');

            // Define foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('cascade');

            // Define a composite primary key to ensure uniqueness of product-category pairs
            // and to provide an index for lookups.
            $table->primary(['product_id', 'category_id']);

            // No timestamps needed for a simple pivot table unless you want to track when a product was added to a category.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_product');
    }
};
