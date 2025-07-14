<?php

namespace Tests\Unit;

use App\Models\Lottery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryTest extends TestCase
{
    use RefreshDatabase;

    public function test_lottery_can_be_created()
    {
        $lottery = Lottery::factory()->create();
        $this->assertDatabaseHas('lotteries', ['id' => $lottery->id]);
    }
}
