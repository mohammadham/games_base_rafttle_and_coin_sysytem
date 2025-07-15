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
            // Stores the amount of BASE COIN to be awarded per ticket purchased directly via gateway for this specific lottery.
            $table->decimal('direct_purchase_coin_reward', 28, 8)->default(0)
                  ->after('price_giving') // Or any other suitable column
                  ->comment('Base coin reward per ticket for direct gateway purchase of this lottery');

            // Optional: If you want to specify which coin type is given as reward.
            // If not present, assume it's always the system's base coin.
            // $table->unsignedBigInteger('reward_coin_type_id')->nullable()->after('direct_purchase_coin_reward');
            // $table->foreign('reward_coin_type_id')->references('id')->on('coin_types')->onDelete('set null');
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
            // if (Schema::hasColumn('lotteries', 'reward_coin_type_id')) {
            //     $table->dropForeign(['reward_coin_type_id']);
            //     $table->dropColumn('reward_coin_type_id');
            // }
            if (Schema::hasColumn('lotteries', 'direct_purchase_coin_reward')) {
                $table->dropColumn('direct_purchase_coin_reward');
            }
        });
    }
};
