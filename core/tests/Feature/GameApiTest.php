<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\CoinType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an API key for authentication
        ApiKey::create([
            'name' => 'Test Game',
            'api_key' => 'test_api_key',
            'status' => 1,
        ]);

        // Create a coin type
        CoinType::create([
            'name' => 'Gold Coin',
            'code' => 'GOLD',
            'symbol' => 'GC',
            'is_base_coin' => true,
            'status' => 1,
        ]);
    }

    public function test_credit_coin()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'X-Api-Key' => 'test_api_key',
        ])->postJson('/api/v1/game/credit-coin', [
            'user_id' => $user->id,
            'coin_code' => 'GOLD',
            'amount' => 100,
            'transaction_id' => 'test_credit_trx',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_coin_balances', [
            'user_id' => $user->id,
            'coin_type_id' => 1,
            'balance' => 100,
        ]);
    }

    public function test_debit_coin()
    {
        $user = User::factory()->create();
        $user->coinBalances()->create([
            'coin_type_id' => 1,
            'balance' => 200,
        ]);

        $response = $this->withHeaders([
            'X-Api-Key' => 'test_api_key',
        ])->postJson('/api/v1/game/debit-coin', [
            'user_id' => $user->id,
            'coin_code' => 'GOLD',
            'amount' => 50,
            'transaction_id' => 'test_debit_trx',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_coin_balances', [
            'user_id' => $user->id,
            'coin_type_id' => 1,
            'balance' => 150,
        ]);
    }
}
