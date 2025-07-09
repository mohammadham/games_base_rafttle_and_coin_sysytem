<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\Status; // Assuming Status::ENABLE is defined

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable()->comment('Filename or path to product image');
            // Price should be in the site's main currency (e.g., defined by gs('cur_text') and gs('cur_sym'))
            // Precision should match general currency precision in the system.
            $table->decimal('price', 28, 8)->comment('Price in site\'s main currency');
            $table->integer('stock')->default(-1)->comment('-1 for unlimited, 0 for out of stock, >0 for specific count');
            $table->boolean('is_digital')->default(false)->comment('Is the product digital or physical?');
            $table->text('digital_good_delivery_info')->nullable()->comment('Info for digital goods delivery (e.g., download link, key generation method)');
            $table->tinyInteger('status')->default(Status::ENABLE)->comment('0: Disabled, 1: Enabled');

            // Optional: For product categories
            // $table->unsignedBigInteger('category_id')->nullable()->index();
            // $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('set null');

            $table->json('meta_data')->nullable()->comment('Additional product attributes like specifications, variations, etc. as JSON');
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
