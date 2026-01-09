<?php

namespace App\Features\Order;

use App\Features\Order\Requests\StoreOrderRequest;
use App\Features\Order\Requests\UpdateOrderRequest;
use App\Features\Order\Resources\OrderResource;
use App\Features\Order\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * [GET] /api/orders
     * Description: Liste des commandes (filtrable par statut, utilisateur, date).
     * @group Order
     * @queryParam status string Filtre par statut.
     * @queryParam user_id integer Filtre par utilisateur.
     * @authenticated false
     */
    public function index(): ResourceCollection
    {
        $orders = $this->orderService->getAllOrders(request()->all());

        return OrderResource::collection($orders);
    }

    /**
     * [GET] /api/orders/{id}
     * Description: Détails d'une commande avec ses items.
     * @group Order
     * @urlParam id required ID de la commande.
     * @authenticated false
     */
    public function show(int $id): OrderResource
    {
        $order = $this->orderService->getOrderById($id);

        return new OrderResource($order);
    }

    /**
     * [POST] /api/orders
     * Description: Création d'une nouvelle commande avec items.
     * @group Order
     * @authenticated false
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $order = $this->orderService->createOrder($request->validated());

        return new OrderResource($order);
    }

    /**
     * [PUT] /api/orders/{id}
     * Description: Mise à jour du statut ou notes d'une commande.
     * @group Order
     * @authenticated false
     */
    public function update(UpdateOrderRequest $request, int $id): OrderResource
    {
        $order = $this->orderService->updateOrder($id, $request->validated());

        return new OrderResource($order);
    }

    /**
     * [DELETE] /api/orders/{id}
     * Description: Annulation/suppression logique d'une commande.
     * @group Order
     * @authenticated false
     */
    public function destroy(int $id): JsonResponse
    {
        $this->orderService->cancelOrder($id);

        return response()->json(null, 204);
    }
}