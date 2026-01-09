<?php

namespace Tests\Feature\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_orders()
    {
        Order::factory()->count(8)->create();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'total_amount', 'user', 'items'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(8, 'data');
    }

    public function test_index_filters_by_status()
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'delivered']);

        $response = $this->getJson('/api/orders?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_show_returns_order_with_details()
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(3), 'items')
            ->create();

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_store_creates_order_successfully()
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 49.99, 'stock' => 20]);
        $product2 = Product::factory()->create(['price' => 99.99, 'stock' => 10]);

        $payload = [
            'user_id' => $user->id,
            'notes' => 'Commande urgente',
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 1],
                ['product_id' => $product2->id, 'quantity' => 2],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_amount', '249.97 €')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_store_fails_validation_if_items_empty()
    {
        $payload = [
            'user_id' => User::factory()->create()->id,
            'items' => [],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_fails_when_product_not_found()
    {
        $payload = [
            'user_id' => User::factory()->create()->id,
            'items' => [
                ['product_id' => 99999, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_update_modifies_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'processing',
            'notes' => 'En préparation',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.notes', 'En préparation');
    }

    public function test_destroy_cancels_order()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_show_returns_404_for_nonexistent_order()
    {
        $response = $this->getJson('/api/orders/99999');

        $response->assertStatus(404);
    }
}