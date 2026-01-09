<?php

namespace App\Features\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function getAllOrders(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['items.product', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15);
    }

    public function getOrderById(int $id): Order
    {
        $order = Order::with(['items.product', 'user'])->find($id);

        if (!$order) {
            throw new ModelNotFoundException("Order not found");
        }

        return $order;
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'user_id' => $data['user_id'],
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour le produit {$product->name}");
                }

                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                // Optionnel : décrémenter le stock
                // $product->decrement('stock', $item['quantity']);
            }

            $order->update(['total_amount' => $total]);

            return $order->load(['user', 'items.product']);
        });
    }

    public function updateOrder(int $id, array $data): Order
    {
       $order = Order::findOrFail($id);

        $order->update([
            'status' => $data['status'] ?? $order->status,
            'notes'  => $data['notes'] ?? $order->notes,
        ]);

        return $order;
    }

    public function cancelOrder(int $id)
    {
        $order = Order::findOrFail($id);

        $order->update(['status' => 'cancelled']);

        return $order;
    }
}