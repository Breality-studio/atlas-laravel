<?php

namespace App\Features\Product;

use App\Features\Product\Requests\StoreProductRequest;
use App\Features\Product\Requests\UpdateProductRequest;
use App\Features\Product\Resources\ProductResource;
use App\Features\Product\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * [GET] /api/products
     * Description: Récupération de la liste des produits avec pagination et filtres avancés.
     * @group Product
     * @queryParam status string Filtrer par statut (active, inactive, out_of_stock). Example: active
     * @queryParam category string Filtrer par catégorie. Example: course
     * @queryParam in_stock boolean Filtrer les produits en stock. Example: true
     * @queryParam search string Recherche par nom ou description. Example: Laravel
     * @authenticated false
     * @return JsonResponse
     */
    public function index(): ResourceCollection
    {
        $products = $this->productService->getAllProducts(request()->all());

        return ProductResource::collection($products);
    }

    /**
     * [GET] /api/products/{id}
     * Description: Récupération des informations détaillées d'un produit spécifique.
     * @group Product
     * @urlParam id int required L'ID du produit. Example: 1
     * @authenticated false
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): ProductResource
    {
        $product = $this->productService->getProductById($id);

        return new ProductResource($product);
    }

    /**
     * [POST] /api/products
     * Description: Création d'un nouveau produit.
     * @group Product
     * @bodyParam name string required Nom du produit. Max:255
     * @bodyParam description string Description du produit.
     * @bodyParam price numeric required Prix du produit (ex: 29.99).
     * @bodyParam stock integer required Quantité en stock.
     * @bodyParam status string Statut (active, inactive, out_of_stock). Default: active
     * @bodyParam image string URL de l'image du produit.
     * @bodyParam categories array Catégories JSON. Example: ["course","ebook"]
     * @authenticated false
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productService->createProduct($request->validated());

        return new ProductResource($product);
    }

    /**
     * [PUT] /api/products/{id}
     * Description: Mise à jour d'un produit existant.
     * @group Product
     * @urlParam id int required L'ID du produit. Example: 1
     * @bodyParam name string Nom du produit. Max:255
     * @bodyParam description string Description du produit.
     * @bodyParam price numeric Prix du produit (ex: 29.99).
     * @bodyParam stock integer Quantité en stock.
     * @bodyParam status string Statut (active, inactive, out_of_stock).
     * @bodyParam image string URL de l'image du produit.
     * @bodyParam categories array Catégories JSON. Example: ["course","ebook"]
     * @authenticated false
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return response()->json(new ProductResource($product));
    }

    /**
     * [DELETE] /api/products/{id}
     * Description: Suppression définitive d'un produit.
     * @group Product
     * @urlParam id int required L'ID du produit. Example: 1
     * @authenticated false
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return response()->json(null, 204);
    }
}