<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Services;

use Illuminate\Support\Collection;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Services\PermissionGroupService;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class PermissionGroupServiceTest extends TestCase
{
    protected PermissionGroupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PermissionGroupService();
    }

    /** @test */
    public function it_builds_group_tree_structure(): void
    {
        Permission::create([
            'name' => 'iam.users.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户管理',
            'display_name' => 'iam.IAM - 用户管理.查看',
        ]);

        Permission::create([
            'name' => 'iam.users.manage',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户管理',
            'display_name' => 'iam.IAM - 用户管理.管理',
        ]);

        Permission::create([
            'name' => 'iam.roles.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.角色管理',
            'display_name' => 'iam.IAM - 角色管理.查看',
        ]);

        $permissions = Permission::all();
        $tree = $this->service->buildGroupTree($permissions);

        $this->assertIsArray($tree);
        $this->assertCount(1, $tree); // 一个模块：iam

        $module = $tree[0];
        $this->assertEquals('iam', $module['key']);
        $this->assertEquals(3, $module['count']); // 3 个权限
        $this->assertCount(2, $module['children']); // 2 个资源组

        // 验证子节点
        $childKeys = array_column($module['children'], 'key');
        $this->assertContains('iam.用户管理', $childKeys);
        $this->assertContains('iam.角色管理', $childKeys);
    }

    /** @test */
    public function it_formats_module_name_from_config(): void
    {
        config(['iam.module_labels.iam' => 'IAM 系统']);

        Permission::create([
            'name' => 'iam.users.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户',
        ]);

        $permissions = Permission::all();
        $tree = $this->service->buildGroupTree($permissions);

        $this->assertEquals('IAM 系统', $tree[0]['label']);
    }

    /** @test */
    public function it_infers_module_name_from_display_name(): void
    {
        Permission::create([
            'name' => 'iam.users.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户',
            'display_name' => 'iam.IAM - 用户管理.查看',
        ]);

        $permissions = Permission::all();
        $tree = $this->service->buildGroupTree($permissions);

        $this->assertEquals('IAM', $tree[0]['label']);
    }

    /** @test */
    public function it_sorts_tree_alphabetically(): void
    {
        Permission::create([
            'name' => 'zzz.test.view',
            'guard_name' => 'sanctum',
            'group' => 'zzz.测试',
        ]);

        Permission::create([
            'name' => 'aaa.test.view',
            'guard_name' => 'sanctum',
            'group' => 'aaa.测试',
        ]);

        $permissions = Permission::all();
        $tree = $this->service->buildGroupTree($permissions);

        $this->assertEquals('aaa', $tree[0]['key']);
        $this->assertEquals('zzz', $tree[1]['key']);
    }

    /** @test */
    public function it_handles_empty_permissions(): void
    {
        $permissions = new Collection();
        $tree = $this->service->buildGroupTree($permissions);

        $this->assertIsArray($tree);
        $this->assertEmpty($tree);
    }

    /** @test */
    public function it_counts_permissions_per_group(): void
    {
        Permission::create([
            'name' => 'iam.users.view',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户',
        ]);

        Permission::create([
            'name' => 'iam.users.manage',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户',
        ]);

        Permission::create([
            'name' => 'iam.users.delete',
            'guard_name' => 'sanctum',
            'group' => 'iam.用户',
        ]);

        $permissions = Permission::all();
        $tree = $this->service->buildGroupTree($permissions);

        $userGroup = $tree[0]['children'][0];
        $this->assertEquals(3, $userGroup['count']);
    }
}
