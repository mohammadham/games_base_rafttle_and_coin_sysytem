<?php

namespace Tests\Unit;

use App\Models\CoinType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_coin_type_can_be_created()
    {
        $coinType = CoinType::factory()->create();
        $this->assertDatabaseHas('coin_types', ['id' => $coinType->id]);
    }

    public function test_get_base_coin()
    {
        CoinType::factory()->create(['is_base_coin' => true]);
        $baseCoin = CoinType::getBaseCoin();
        $this->assertNotNull($baseCoin);
        $this->assertTrue($baseCoin->is_base_coin);
    }

    public function test_convert_to_base_coin()
    {
        $coinType = CoinType::factory()->create(['base_coin_value_multiplier' => 10]);
        $this->assertEquals(100, $coinType->convertToBaseCoin(10));
    }

    public function test_convert_from_base_coin()
    {
        $coinType = CoinType::factory()->create(['base_coin_value_multiplier' => 10]);
        $this->assertEquals(10, $coinType->convertFromBaseCoin(100));
    }
}
