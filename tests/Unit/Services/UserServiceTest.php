<?php

namespace Tests\Unit\Services;

use App\Features\User\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_get_all_users_returns_paginated_users()
    {
        User::factory()->count(5)->create();

        $users = $this->userService->getAllUsers();

        $this->assertCount(5, $users->items());
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $users);
    }

    public function test_get_user_by_id_returns_user()
    {
        $user = User::factory()->create();

        $foundUser = $this->userService->getUserById($user->id);

        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_create_user_creates_new_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $user = $this->userService->createUser($data);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertNotEquals('password', $user->password); // Vérifie le hachage
    }

    public function test_update_user_updates_existing_user()
    {
        $user = User::factory()->create();
        $data = ['name' => 'Updated Name'];

        $updatedUser = $this->userService->updateUser($user->id, $data);

        $this->assertEquals('Updated Name', $updatedUser->name);
    }

    public function test_delete_user_deletes_user()
    {
        $user = User::factory()->create();

        $this->userService->deleteUser($user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}