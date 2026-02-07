<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 创建超级管理员角色
        $superAdminRole = Role::query()->firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'sanctum'],
            ['display_name' => '超级管理员']
        );

        // 分配所有权限给超级管理员
        $superAdminRole->syncPermissions(Permission::all());

        $this->command->info('角色数据已初始化');
    }
}
