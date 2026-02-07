<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Services;

use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Services\MenuService;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class MenuServiceTest extends TestCase
{
    protected MenuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MenuService::class);
    }

    /** @test */
    public function it_returns_all_menus_for_super_admin(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        Menu::create(['name' => 'Menu1', 'path' => '/1', 'is_enabled' => true]);
        Menu::create(['name' => 'Menu2', 'path' => '/2', 'is_enabled' => true]);
        Menu::create(['name' => 'Menu3', 'path' => '/3', 'is_enabled' => false]); // 禁用的也不显示

        $menus = $this->service->getMenuTreeForUser($user);

        $this->assertCount(2, $menus); // 只有启用的菜单
    }

    /** @test */
    public function it_filters_menus_by_role(): void
    {
        $user = User::create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => 'password',
        ]);

        $editorRole = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        $user->assignRole($editorRole);

        $menu1 = Menu::create(['name' => 'Public', 'path' => '/public', 'is_enabled' => true, 'is_public' => true]);
        $menu2 = Menu::create(['name' => 'Editor', 'path' => '/editor', 'is_enabled' => true]);
        $menu3 = Menu::create(['name' => 'Admin', 'path' => '/admin', 'is_enabled' => true]);

        $menu2->roles()->attach($editorRole);

        $menus = $this->service->getMenuTreeForUser($user);

        $this->assertCount(2, $menus); // Public + Editor
        $menuNames = array_column($menus, 'name');
        $this->assertContains('Public', $menuNames);
        $this->assertContains('Editor', $menuNames);
        $this->assertNotContains('Admin', $menuNames);
    }

    /** @test */
    public function it_shows_public_menus_to_all_users(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        Menu::create(['name' => 'Public', 'path' => '/public', 'is_enabled' => true, 'is_public' => true]);
        Menu::create(['name' => 'Private', 'path' => '/private', 'is_enabled' => true]);

        $menus = $this->service->getMenuTreeForUser($user);

        $this->assertCount(1, $menus);
        $this->assertEquals('Public', $menus[0]['name']);
    }

    /** @test */
    public function it_hides_disabled_menus(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        Menu::create(['name' => 'Enabled', 'path' => '/enabled', 'is_enabled' => true]);
        Menu::create(['name' => 'Disabled', 'path' => '/disabled', 'is_enabled' => false]);

        $menus = $this->service->getMenuTreeForUser($user);

        $this->assertCount(1, $menus);
        $this->assertEquals('Enabled', $menus[0]['name']);
    }

    /** @test */
    public function it_builds_tree_structure_with_children(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        $parent = Menu::create(['name' => 'Parent', 'path' => '/parent', 'is_enabled' => true]);
        $child1 = Menu::create(['name' => 'Child1', 'path' => '/parent/child1', 'parent_id' => $parent->id, 'is_enabled' => true]);
        $child2 = Menu::create(['name' => 'Child2', 'path' => '/parent/child2', 'parent_id' => $parent->id, 'is_enabled' => true]);

        $menus = $this->service->getMenuTreeForUser($user);

        $this->assertCount(1, $menus); // 只有一个根菜单
        $this->assertEquals('Parent', $menus[0]['name']);
        $this->assertCount(2, $menus[0]['children']);
    }

    /** @test */
    public function it_caches_menu_tree(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        Menu::create(['name' => 'Menu1', 'path' => '/1', 'is_enabled' => true]);

        // 第一次调用
        $menus1 = $this->service->getMenuTreeForUser($user);

        // 添加新菜单（但不应该出现在缓存结果中）
        Menu::create(['name' => 'Menu2', 'path' => '/2', 'is_enabled' => true]);

        // 第二次调用（应该从缓存获取）
        $menus2 = $this->service->getMenuTreeForUser($user);

        $this->assertCount(1, $menus2); // 仍然是 1 个，因为使用了缓存

        // 强制刷新缓存
        $menus3 = $this->service->getMenuTreeForUser($user, true);

        $this->assertCount(2, $menus3); // 现在有 2 个了
    }
}
