<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Models;

use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class MenuTest extends TestCase
{
    /** @test */
    public function it_can_create_a_menu(): void
    {
        $menu = Menu::create([
            'name' => 'Home',
            'path' => '/',
            'component' => 'Home',
        ]);

        $this->assertDatabaseHas('iam_menus', [
            'name' => 'Home',
            'path' => '/',
        ]);
    }

    /** @test */
    public function it_can_have_parent_and_children(): void
    {
        $parent = Menu::create([
            'name' => 'Parent',
            'path' => '/parent',
        ]);

        $child = Menu::create([
            'name' => 'Child',
            'path' => '/parent/child',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($parent->children->contains($child));
    }

    /** @test */
    public function it_can_build_tree_structure(): void
    {
        $root = Menu::create(['name' => 'Root', 'path' => '/', 'sort_order' => 1]);
        $child1 = Menu::create(['name' => 'Child1', 'path' => '/c1', 'parent_id' => $root->id, 'sort_order' => 1]);
        $child2 = Menu::create(['name' => 'Child2', 'path' => '/c2', 'parent_id' => $root->id, 'sort_order' => 2]);

        $menus = Menu::all();
        $tree = Menu::buildTree($menus);

        $this->assertCount(1, $tree); // Only root
        $this->assertEquals('Root', $tree[0]->name);
        $this->assertCount(2, $tree[0]->children);
        $this->assertEquals('Child1', $tree[0]->children[0]->name);
        $this->assertEquals('Child2', $tree[0]->children[1]->name);
    }

    /** @test */
    public function it_checks_visibility_for_super_admin(): void
    {
        $menu = Menu::create([
            'name' => 'Admin Menu',
            'path' => '/admin',
            'is_enabled' => true,
        ]);

        $this->assertTrue($menu->isVisibleFor(['super-admin']));
    }

    /** @test */
    public function it_checks_visibility_for_public_menu(): void
    {
        $menu = Menu::create([
            'name' => 'Public Menu',
            'path' => '/public',
            'is_enabled' => true,
            'is_public' => true,
        ]);

        $this->assertTrue($menu->isVisibleFor(['user']));
        $this->assertTrue($menu->isVisibleFor([]));
    }

    /** @test */
    public function it_checks_visibility_for_role_based_menu(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        $menu = Menu::create([
            'name' => 'Editor Menu',
            'path' => '/editor',
            'is_enabled' => true,
        ]);

        $menu->roles()->attach($role);

        $this->assertTrue($menu->fresh()->isVisibleFor(['editor']));
        $this->assertFalse($menu->fresh()->isVisibleFor(['viewer']));
    }

    /** @test */
    public function it_hides_disabled_menu(): void
    {
        $menu = Menu::create([
            'name' => 'Disabled Menu',
            'path' => '/disabled',
            'is_enabled' => false,
        ]);

        $this->assertFalse($menu->isVisibleFor(['super-admin']));
        $this->assertFalse($menu->isVisibleFor([]));
    }

    /** @test */
    public function it_can_attach_roles(): void
    {
        $menu = Menu::create([
            'name' => 'Test Menu',
            'path' => '/test',
        ]);

        $role = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        $menu->roles()->attach($role);

        $this->assertTrue($menu->roles->contains($role));
    }

    /** @test */
    public function it_casts_meta_to_array(): void
    {
        $meta = ['icon' => 'home', 'title' => 'Home Page'];

        $menu = Menu::create([
            'name' => 'Home',
            'path' => '/',
            'meta' => $meta,
        ]);

        $this->assertIsArray($menu->meta);
        $this->assertEquals($meta, $menu->meta);
    }
}
