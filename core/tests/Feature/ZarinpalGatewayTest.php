<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ZarinpalGatewayTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    public function test_can_initiate_payment()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $gateway = \App\Models\Gateway::create([
            'code' => 'zarinpal',
            'name' => 'Zarinpal',
            'alias' => 'zarinpal',
            'description' => 'Zarinpal Payment Gateway',
            'extra' => json_encode([
                'merchant_id' => 'test',
                'sandbox_mode' => true,
                'currency_note' => 'پرداخت به ریال انجام خواهد شد',
            ]),
        ]);

        $gatewayCurrency = \App\Models\GatewayCurrency::create([
            'name' => 'Toman',
            'currency' => 'IRT',
            'symbol' => 'تومان',
            'method_code' => $gateway->code,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'fixed_charge' => 0,
            'percent_charge' => 0,
            'rate' => 1,
        ]);

        $response = $this->post(route('user.deposit.insert'), [
            'amount' => 10000,
            'gateway_code' => 'zarinpal',
            'currency' => 'IRT',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('https://sandbox.zarinpal.com/pg/StartPay/', $response->headers->get('Location'));
    }
}
