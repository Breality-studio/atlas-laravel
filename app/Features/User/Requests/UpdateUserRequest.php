<?php

namespace App\Features\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
     * Règles de validation pour la mise à jour d'un utilisateur.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $this->user,
            'password' => 'sometimes|required|string|min:8|confirmed',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nouveau nom de l’utilisateur (optionnel).',
                'example' => 'Jean Martin',
            ],
            'email' => [
                'description' => 'Nouvelle adresse email (doit être unique).',
                'example' => 'jean.martin@example.com',
            ],
            'password' => [
                'description' => 'Nouveau mot de passe (minimum 8 caractères).',
                'example' => 'newpassword456',
            ],
            'password_confirmation' => [
                'description' => 'Confirmation du nouveau mot de passe.',
                'example' => 'newpassword456',
            ],
        ];
    }
}