<?php

namespace App\Features\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transforme la ressource en un tableau.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => number_format($this->price, 2) . ' €',
            'stock' => (int) $this->stock,
            'status' => $this->status,
            'in_stock' => $this->stock > 0,
            'image' => $this->image,
            'categories' => $this->categories ?? [],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}