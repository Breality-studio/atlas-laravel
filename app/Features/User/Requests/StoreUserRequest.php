<?php

namespace App\Features\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
     * Règles de validation pour la création d'un utilisateur.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Exemples de paramètres pour la documentation Scribe.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nom complet de l’utilisateur.',
                'example' => 'Jean Dupont',
            ],
            'email' => [
                'description' => 'Adresse email unique de l’utilisateur.',
                'example' => 'jean.dupont@example.com',
            ],
            'password' => [
                'description' => 'Mot de passe (minimum 8 caractères).',
                'example' => 'secret123',
            ],
            'password_confirmation' => [
                'description' => 'Confirmation du mot de passe.',
                'example' => 'secret123',
            ],
        ];
    }
}