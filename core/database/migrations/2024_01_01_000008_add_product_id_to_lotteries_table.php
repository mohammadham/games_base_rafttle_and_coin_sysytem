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
        Schema::table('lotteries', function (Blueprint $table) {
            // Add product_id column, make it nullable and an unsigned big integer
            $table->unsignedBigInteger('product_id')->nullable()->after('competition_id'); // Or after another relevant column

            // Add foreign key constraint to products table
            // onDelete('set null') means if the product is deleted, the lottery's product_id will be set to null
            // This allows the lottery to exist even if its associated product is removed.
            // If a lottery MUST have a product, you might consider onDelete('cascade') or restrict deletion of products with active lotteries.
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotteries', function (Blueprint $table) {
            // To correctly drop a foreign key, you often need to know its generated name
            // or drop it by column. Laravel < 5.7 might need array for column.
            // $table->dropForeign(['product_id']); // If named automatically like lotteries_product_id_foreign
            // A safer way if you don't know the exact constraint name:
            if (Schema::hasColumn('lotteries', 'product_id')) {
                 // First, try to drop the foreign key constraint if it exists.
                 // The name of the foreign key constraint can vary.
                 // Common pattern: table_column_foreign. So, 'lotteries_product_id_foreign'.
                 // You might need to inspect your DB or other migrations to find the exact name if this fails.
                try {
                    $table->dropForeign('lotteries_product_id_foreign');
                } catch (\Exception $e) {
                    // Log or handle if dropping by conventional name fails
                    // This might happen if the constraint has a custom name.
                    // In such a case, you'd need to find the specific constraint name.
                    // For now, we'll proceed to try dropping the column.
                }
                $table->dropColumn('product_id');
            }
        });
    }
};
