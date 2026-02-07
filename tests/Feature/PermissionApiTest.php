<?php

namespace WeiJuKeJi\LaravelIam\Tests\Feature;

use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class PermissionApiTest extends TestCase
{
    /** @test */
    public function it_requires_authentication_to_list_permissions(): void
    {
        $response = $this->getJson('/api/iam/permissions');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_permission_to_list_permissions(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/permissions');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_list_permissions_with_permission(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.permissions.view',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        Permission::create([
            'name' => 'test.permission.1',
            'guard_name' => 'sanctum',
            'display_name' => 'Test Permission 1',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'display_name', 'guard_name'],
                    ],
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_permission_groups(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.permissions.view',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        Permission::create([
            'name' => 'iam.users.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户管理',
            'display_name' => 'iam.IAM - 用户管理.查看',
        ]);

        Permission::create([
            'name' => 'iam.roles.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.角色管理',
            'display_name' => 'iam.IAM - 角色管理.查看',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/permissions/groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'tree' => [
                        '*' => ['key', 'label', 'count', 'children'],
                    ],
                    'total',
                ],
            ]);

        $tree = $response->json('data.tree');
        $this->assertNotEmpty($tree);
    }

    /** @test */
    public function super_admin_can_access_all_permissions(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        Permission::create([
            'name' => 'test.permission',
            'guard_name' => 'sanctum',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/permissions');

        $response->assertStatus(200);
    }
}
