<?php

namespace App\Features\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Récupère tous les utilisateurs avec pagination.
     *
     * @return LengthAwarePaginator
     */
    public function getAllUsers(): LengthAwarePaginator
    {
        return User::paginate(15);
    }

    /**
     * Récupère un utilisateur par son ID.
     *
     * @param int $id
     * @return User
     */
    public function getUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    /**
     * Met à jour un utilisateur existant.
     *
     * @param int $id
     * @param array $data
     * @return User
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUserById($id);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user;
    }

    /**
     * Supprime un utilisateur.
     *
     * @param int $id
     * @return void
     */
    public function deleteUser(int $id): void
    {
        $user = $this->getUserById($id);
        $user->delete();
    }
}