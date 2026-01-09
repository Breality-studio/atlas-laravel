<?php

namespace App\Features\Product;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Récupère tous les produits avec pagination et filtres avancés.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllProducts(array $filters = []): LengthAwarePaginator
    {
        return Cache::remember("products.{$this->getCacheKey($filters)}", 300, function () use ($filters) {
            return Product::query()
                ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
                ->when(isset($filters['category']), fn($q) => $q->whereJsonContains('categories', $filters['category']))
                ->when(isset($filters['in_stock']), fn($q) => $q->inStock())
                ->when(isset($filters['search']), fn($q) => $q
                    ->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%'))
                ->active()
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        });
    }

    /**
     * Récupère un produit par son ID.
     *
     * @param int $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProductById(int $id): Product
    {
        return Product::findOrFail($id);
    }

    /**
     * Crée un nouveau produit.
     *
     * @param array $data
     * @return Product
     */
    public function createProduct(array $data): Product
    {
        Cache::clear();
        return Product::create($data);
    }

    /**
     * Met à jour un produit existant.
     *
     * @param int $id
     * @param array $data
     * @return Product
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->getProductById($id);

        Cache::clear();
        $product->update($data);
        return $product->refresh();
    }

    /**
     * Supprime un produit.
     *
     * @param int $id
     * @return void
     */
    public function deleteProduct(int $id): void
    {
        $product = $this->getProductById($id);
        Cache::clear();
        $product->delete();
    }

    /**
     * Génère une clé de cache unique basée sur les filtres.
     *
     * @param array $filters
     * @return string
     */
    private function getCacheKey(array $filters): string
    {
        return md5(json_encode($filters));
    }

    /**
     * Vide le cache des produits.
     *
     * @return void
     */
    private function clearProductsCache(): void
    {
        Cache::forgetByPattern('products.*');
        Cache::forgetByPattern('product.*');
    }
}