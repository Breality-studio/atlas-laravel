<?php

namespace Tests\Feature\Controllers;

use App\Models\Product;
use App\Features\Product\Requests\StoreProductRequest;
use App\Features\Product\Requests\UpdateProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_products()
    {
        Product::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_with_filters()
    {
        Product::factory()->create(['status' => 'active', 'categories' => ['course']]);
        Product::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/products?status=active&category=course');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $product->id);
        $response->assertJsonPath('data.in_stock', $product->stock > 0);
    }

    public function test_store_creates_new_product()
    {
        $data = [
            'name' => 'Vue.js Masterclass',
            'description' => 'Formation Vue.js avancée',
            'price' => 129.99,
            'stock' => 25,
            'status' => 'active',
            'categories' => ['course', 'formation'],
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Vue.js Masterclass']);
        $response->assertJsonPath('data.price', '129.99 €');
    }

    public function test_store_validation_fails_with_invalid_data()
    {
        $data = ['name' => '', 'price' => -10, 'stock' => -5];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    public function test_update_updates_product()
    {
        $product = Product::factory()->create();
        $data = ['name' => 'React Avancé', 'price' => 199.99];

        $response = $this->putJson("/api/products/{$product->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'React Avancé'
        ]);
    }

    public function test_destroy_deletes_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_show_returns_404_for_nonexistent_product()
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);
    }
}