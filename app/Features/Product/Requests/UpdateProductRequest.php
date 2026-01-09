<?php

namespace App\Features\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'un produit.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:1',
            'stock' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|in:active,inactive,out_of_stock',
            'image' => 'nullable|url',
            'categories' => 'nullable|array|max:5',
            'categories.*' => 'in:course,ebook,webinar,mentorat,formation',
        ];
    }

    public function bodyParameters(): array
{
    return [
        'name' => [
            'description' => 'Nouveau nom du produit (optionnel).',
            'example' => 'Formation Laravel Expert',
        ],
        'description' => [
            'description' => 'Nouvelle description.',
            'example' => 'Formation mise à jour pour Laravel 11.',
        ],
        'price' => [
            'description' => 'Nouveau prix.',
            'example' => 199.99,
        ],
        'stock' => [
            'description' => 'Nouvelle quantité en stock.',
            'example' => 30,
        ],
        'status' => [
            'description' => 'Nouveau statut.',
            'example' => 'active',
        ],
        'image' => [
            'description' => 'Nouvelle URL d’image.',
            'example' => 'https://example.com/images/laravel-expert.jpg',
        ],
        'categories' => [
            'description' => 'Nouvelles catégories.',
            'example' => ['course', 'expert'],
        ],
    ];
}
}