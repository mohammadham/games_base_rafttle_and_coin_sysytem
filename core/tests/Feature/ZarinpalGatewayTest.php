<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Lib\PaymentGateway\PaymentManager;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZarinpalGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gateway::updateOrCreate(
            ['code' => 'zarinpal'],
            [
                'name' => 'Zarinpal',
                'alias' => 'zarinpal',
                'status' => 1,
                'extra' => json_encode([
                    'merchant_id' => 'test_merchant_id',
                    'sandbox_mode' => true,
                    'currency_note' => 'پرداخت به ریال انجام خواهد شد',
                ]),
                'supported_currencies' => json_encode(['IRT', 'IRR']),
            ]
        );
    }

    public function test_zarinpal_payment_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->id,
            'method_code' => 'zarinpal',
            'amount' => 10000,
            'status' => Status::PAYMENT_INITIATE,
        ]);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'authority' => 'test_authority',
                ],
            ]),
        ]);

        $manager = new PaymentManager();
        $zarinpal = $manager->getGateway('zarinpal');

        $result = $zarinpal->requestPayment($deposit->amount, 'IRT', 'test', route('ipn.zarinpal'));

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('redirect_url', $result);
    }

    public function test_zarinpal_payment_verification()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deposit = Deposit::factory()->create([
            'user_id' => $user->id,
            'method_code' => 'zarinpal',
            'amount' => 10000,
            'status' => Status::PAYMENT_INITIATE,
            'trx' => 'test_trx',
        ]);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'ref_id' => 'test_ref_id',
                ],
            ]),
        ]);

        $manager = new PaymentManager();
        $zarinpal = $manager->getGateway('zarinpal');

        $request = new \Illuminate\Http\Request();
        $request->replace(['Authority' => 'test_authority', 'Status' => 'OK']);

        $result = $zarinpal->verifyPayment($request, ['payment_id' => 'test_authority', 'amount_in_rial' => $deposit->amount * 10]);

        $this->assertTrue($result['success']);
        $this->assertEquals('test_ref_id', $result['transaction_id']);
    }
}
