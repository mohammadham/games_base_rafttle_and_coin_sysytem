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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('image_path')->comment('Filename or path to the image, relative to product image directory');
            $table->boolean('is_featured')->default(false)->comment('Is this the main/default image for the product?');
            $table->integer('sort_order')->default(0)->comment('To control the order of images in a gallery display');
            $table->timestamps();

            // Foreign key constraint to the products table
            // Ensures that if a product is deleted, its associated images are also deleted (onDelete('cascade'))
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_images');
    }
};
