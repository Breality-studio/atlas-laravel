<?php

namespace App\Features\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function bodyParameters(): array
{
    return [
        'notes' => [
            'description' => 'Notes ou instructions supplémentaires pour la commande.',
            'example' => 'Livraison urgente souhaitée avant le 15 janvier.',
        ],
        'items' => [
            'description' => 'Liste des articles de la commande (au moins un).',
            'example' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ],
                [
                    'product_id' => 3,
                    'quantity' => 1,
                ],
            ],
        ],
    ];
}
}