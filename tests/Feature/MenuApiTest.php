<?php

namespace WeiJuKeJi\LaravelIam\Tests\Feature;

use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class MenuApiTest extends TestCase
{
    /** @test */
    public function it_requires_authentication_to_list_menus(): void
    {
        $response = $this->getJson('/api/iam/menus');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_permission_to_list_menus(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/menus');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_list_menus_with_permission(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.menus.view',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        Menu::create([
            'name' => 'TestMenu',
            'path' => '/test',
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/menus');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'path', 'is_enabled'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_create_menu_with_permission(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.menus.manage',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/iam/menus', [
                'name' => 'NewMenu',
                'path' => '/new',
                'component' => 'NewComponent',
                'is_enabled' => true,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('iam_menus', [
            'name' => 'NewMenu',
            'path' => '/new',
        ]);
    }

    /** @test */
    public function it_can_delete_menu_without_children(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.menus.manage',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        $menu = Menu::create([
            'name' => 'TestMenu',
            'path' => '/test',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/iam/menus/{$menu->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('iam_menus', [
            'id' => $menu->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_menu_with_children(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.menus.manage',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        $parent = Menu::create([
            'name' => 'Parent',
            'path' => '/parent',
        ]);

        $child = Menu::create([
            'name' => 'Child',
            'path' => '/parent/child',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/iam/menus/{$parent->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('iam_menus', [
            'id' => $parent->id,
        ]);
    }

    /** @test */
    public function it_can_get_menu_tree(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'iam.menus.view',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        $parent = Menu::create(['name' => 'Parent', 'path' => '/parent']);
        $child = Menu::create(['name' => 'Child', 'path' => '/child', 'parent_id' => $parent->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/iam/menus/tree');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'list' => [
                        '*' => ['id', 'name', 'path', 'children'],
                    ],
                    'total',
                ],
            ]);
    }
}
