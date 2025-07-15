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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('For subcategories, references id of this table');
            $table->text('description')->nullable();
            $table->string('image')->nullable()->comment('Optional image for the category');
            $table->tinyInteger('status')->default(Status::ENABLE)->comment('0: Disabled, 1: Enabled');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('product_categories')->onDelete('set null');
            // Using onDelete('set null') for parent_id means if a parent category is deleted,
            // its children become top-level categories. You might prefer onDelete('cascade')
            // if you want children to be deleted with the parent, or handle this in application logic.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_categories');
    }
};
