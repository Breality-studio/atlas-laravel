<?php

namespace Tests\Unit\Services;

use App\Features\Product\ProductService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
        Cache::clear();
    }

    public function test_get_all_products_returns_paginated_products()
    {
        Product::factory()->count(5)->create(['status' => 'active']);

        $products = $this->productService->getAllProducts();

        $this->assertInstanceOf(LengthAwarePaginator::class, $products);
        $this->assertCount(5, $products->items());
    }

    public function test_get_all_products_with_filters()
    {
        Product::factory()->create(['status' => 'active', 'categories' => ['course']]);
        Product::factory()->create(['status' => 'inactive']);

        $products = $this->productService->getAllProducts(['status' => 'active', 'category' => 'course']);

        $this->assertCount(1, $products->items());
    }

    public function test_get_product_by_id_returns_product()
    {
        $product = Product::factory()->create();

        $foundProduct = $this->productService->getProductById($product->id);

        $this->assertEquals($product->id, $foundProduct->id);
    }

    public function test_create_product_creates_new_product()
    {
        $data = [
            'name' => 'Laravel Course',
            'description' => 'Formation complète Laravel',
            'price' => 99.99,
            'stock' => 50,
            'status' => 'active',
            'categories' => ['course', 'formation'],
        ];

        $product = $this->productService->createProduct($data);

        $this->assertDatabaseHas('products', ['name' => 'Laravel Course']);
        $this->assertEquals(99.99, $product->price);
    }

    public function test_update_product_updates_existing_product()
    {
        $product = Product::factory()->create();
        $data = ['price' => 149.99, 'stock' => 75];

        $updatedProduct = $this->productService->updateProduct($product->id, $data);

        $this->assertEquals(149.99, $updatedProduct->price);
        $this->assertEquals(75, $updatedProduct->stock);
    }

    public function test_delete_product_deletes_product()
    {
        $product = Product::factory()->create();

        $this->productService->deleteProduct($product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}