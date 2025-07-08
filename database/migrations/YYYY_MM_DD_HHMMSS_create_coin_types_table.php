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
        Schema::create('coin_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Display name of the coin type, e.g., Gold Coin, Site Credit');
            $table->string('code', 50)->unique()->comment('Unique code for system use, e.g., GOLD, CREDIT, MAIN');
            $table->string('symbol', 20)->nullable()->comment('Symbol for the coin, e.g., GC, CR, $');
            $table->boolean('is_base_coin')->default(false)->index()->comment('True if this is the base coin against which others are valued');
            $table->decimal('base_coin_value_multiplier', 28, 18)->default(1.000000000000000000)->comment('Value of 1 unit of THIS coin in terms of the BASE coin. For base coin, this is 1.');
            $table->tinyInteger('status')->default(Status::ENABLE)->comment('Refers to App\Constants\Status::ENABLE or Status::DISABLE');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->comment('Admin who created this coin type');
            $table->json('meta')->nullable()->comment('Additional properties like display color, icon path, precision for display, etc.');
            $table->timestamps(); // created_at, updated_at

            $table->foreign('created_by_admin_id')->references('id')->on('admins')->onDelete('set null');
            // Note: A unique constraint on (is_base_coin = true) is hard to do directly in all DBs.
            // This logic is typically enforced at the application level (e.g., in the model's saving event).
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coin_types');
    }
};
