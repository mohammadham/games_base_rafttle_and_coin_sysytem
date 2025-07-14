<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Gateway::updateOrCreate(
            ['code' => 'zarinpal'],
            [
                'name' => 'Zarinpal',
                'alias' => 'zarinpal',
                'status' => 1,
                'extra' => json_encode([
                    'merchant_id' => 'YOUR_MERCHANT_ID',
                    'sandbox_mode' => true,
                    'currency_note' => 'پرداخت به ریال انجام خواهد شد',
                ]),
                'supported_currencies' => json_encode(['IRT', 'IRR']),
            ]
        );
    }
}
