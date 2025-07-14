<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_has_full_name_attribute()
    {
        $user = User::factory()->create(['firstname' => 'John', 'lastname' => 'Doe']);
        $this->assertEquals('John Doe', $user->fullname);
    }

    public function test_user_has_mobile_number_attribute()
    {
        $user = User::factory()->create(['dial_code' => '+1', 'mobile' => '1234567890']);
        $this->assertEquals('+11234567890', $user->mobile_number);
    }
}
