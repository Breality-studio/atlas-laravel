<?php

namespace App\Features\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
     * Règles de validation pour la création d'un produit.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:1',
            'status' => 'sometimes|in:active,inactive,out_of_stock',
            'image' => 'nullable|url',
            'categories' => 'nullable|array|max:5',
            'categories.*' => 'in:course,ebook,webinar,mentorat,formation',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'price.min' => 'Le prix doit être positif.',
            'stock.min' => 'Le stock ne peut pas être négatif.',
            'categories.max' => 'Maximum 5 catégories autorisées.',
        ];
    }

    public function bodyParameters(): array
{
    return [
        'name' => [
            'description' => 'Nom du produit.',
            'example' => 'Formation Laravel Avancée',
        ],
        'description' => [
            'description' => 'Description détaillée du produit.',
            'example' => 'Une formation complète sur Laravel 11 avec des projets réels.',
        ],
        'price' => [
            'description' => 'Prix du produit en euros (positif).',
            'example' => 149.99,
        ],
        'stock' => [
            'description' => 'Quantité disponible en stock.',
            'example' => 50,
        ],
        'status' => [
            'description' => 'Statut du produit.',
            'example' => 'active',
        ],
        'image' => [
            'description' => 'URL de l’image du produit.',
            'example' => 'https://example.com/images/laravel-course.jpg',
        ],
        'categories' => [
            'description' => 'Liste des catégories (max 5).',
            'example' => ['course', 'formation', 'laravel'],
        ],
    ];
}
}