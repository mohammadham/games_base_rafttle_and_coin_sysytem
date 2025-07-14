<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created()
    {
        $product = Product::factory()->create();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_generate_unique_slug()
    {
        Product::factory()->create(['name' => 'Test Product', 'slug' => 'test-product']);
        $slug = Product::generateUniqueSlug('Test Product');
        $this->assertEquals('test-product-1', $slug);
    }
}
