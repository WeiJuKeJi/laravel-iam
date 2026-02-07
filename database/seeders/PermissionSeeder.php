<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use WeiJuKeJi\LaravelIam\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'iam.users.view', 'display_name' => '查看用户', 'group' => '用户管理'],
            ['name' => 'iam.users.manage', 'display_name' => '管理用户', 'group' => '用户管理'],
            ['name' => 'iam.roles.view', 'display_name' => '查看角色', 'group' => '角色管理'],
            ['name' => 'iam.roles.manage', 'display_name' => '管理角色', 'group' => '角色管理'],
            ['name' => 'iam.permissions.view', 'display_name' => '查看权限', 'group' => '权限管理'],
            ['name' => 'iam.permissions.manage', 'display_name' => '管理权限', 'group' => '权限管理'],
            ['name' => 'iam.permissions.groups', 'display_name' => '查看权限分组', 'group' => '权限管理'],
            ['name' => 'iam.menus.view', 'display_name' => '查看菜单', 'group' => '菜单管理'],
            ['name' => 'iam.menus.manage', 'display_name' => '管理菜单', 'group' => '菜单管理'],
            ['name' => 'iam.departments.view', 'display_name' => '查看部门', 'group' => '部门管理'],
            ['name' => 'iam.departments.manage', 'display_name' => '管理部门', 'group' => '部门管理'],
            ['name' => 'iam.login-logs.view', 'display_name' => '查看登录日志', 'group' => '登录日志'],
            ['name' => 'iam.login-logs.my', 'display_name' => '查看我的登录日志', 'group' => '登录日志'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'sanctum'],
                $permission
            );
        }

        $this->command->info('权限数据已初始化');
    }
}
