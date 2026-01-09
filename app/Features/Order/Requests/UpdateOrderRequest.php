<?php

namespace App\Features\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'Nouveau statut de la commande.',
                'example' => 'confirmed',
            ],
            'notes' => [
                'description' => 'Notes mises à jour.',
                'example' => 'Client contacté par téléphone.',
            ],
        ];
    }
}