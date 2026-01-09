<?php

namespace App\Features\User;

use App\Features\User\Requests\StoreUserRequest;
use App\Features\User\Requests\UpdateUserRequest;
use App\Features\User\Resources\UserResource;
use App\Features\User\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * [GET] /api/users
     * Description: Récupération de la liste des utilisateurs avec pagination.
     * @group User
     * @authenticated false
     * @return JsonResponse
     */
    public function index(): ResourceCollection
    {
        $users = $this->userService->getAllUsers();

        return UserResource::collection($users);
    }

    /**
     * [GET] /api/users/{id}
     * Description: Récupération des informations détaillées d'un utilisateur spécifique.
     * @group User
     * @authenticated false
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): UserResource
    {
        $user = $this->userService->getUserById($id);

        return new UserResource($user);
    }

    /**
     * [POST] /api/users
     * Description: Création d'un nouvel utilisateur.
     * @group User
     * @authenticated false
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->userService->createUser($request->validated());

        return new UserResource($user);
    }

    /**
     * [PUT] /api/users/{id}
     * Description: Mise à jour des informations d'un utilisateur existant.
     * @group User
     * @authenticated false
     * @param UpdateUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = $this->userService->updateUser($id, $request->validated());

        return new UserResource($user);
    }

    /**
     * [DELETE] /api/users/{id}
     * Description: Suppression d'un utilisateur.
     * @group User
     * @authenticated false
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return response()->json(null, 204);
    }
}