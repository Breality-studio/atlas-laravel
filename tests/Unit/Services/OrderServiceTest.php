<?php

namespace Tests\Unit\Services;

use App\Features\Order\OrderService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    public function test_get_all_orders_returns_paginated_collection_with_relations()
    {
        $user = User::factory()->create();
        
        Order::factory()->count(5)->create(['user_id' => $user->id]);

        $orders = $this->orderService->getAllOrders();

        $this->assertInstanceOf(LengthAwarePaginator::class, $orders);
        $this->assertCount(5, $orders->items());
    }

    public function test_get_all_orders_applies_status_filter()
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'delivered']);

        $orders = $this->orderService->getAllOrders(['status' => 'pending']);

        $this->assertCount(3, $orders->items());
    }

    public function test_get_order_by_id_loads_order_with_relations()
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(2), 'items')
            ->create();

        $foundOrder = $this->orderService->getOrderById($order->id);

        $this->assertEquals($order->id, $foundOrder->id);
        $this->assertCount(2, $foundOrder->items);
        $this->assertNotNull($foundOrder->items->first()->product);
    }

    public function test_create_order_creates_order_and_items_and_calculates_total()
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 50.00, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 30.00, 'stock' => 5]);

        $data = [
            'user_id' => $user->id,
            'notes' => 'Livraison rapide souhaitée',
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
        ];

        $order = $this->orderService->createOrder($data);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'total_amount' => 130.00,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);
    }

    public function test_create_order_throws_exception_when_stock_insufficient()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $product = Product::factory()->create(['price' => 100, 'stock' => 1]);

        $data = [
            'user_id' => User::factory()->create()->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ];

        $this->orderService->createOrder($data);
    }

    public function test_update_order_updates_status_and_notes()
    {
        $order = Order::factory()->create(['status' => 'pending', 'notes' => null]);

        $updatedOrder = $this->orderService->updateOrder($order->id, [
            'status' => 'confirmed',
            'notes' => 'Client contacté',
        ]);

        $this->assertEquals('confirmed', $updatedOrder->status);
        $this->assertEquals('Client contacté', $updatedOrder->notes);
    }

    public function test_cancel_order_sets_status_to_cancelled()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->orderService->cancelOrder($order->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_get_order_by_id_throws_exception_if_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->orderService->getOrderById(99999);
    }
}