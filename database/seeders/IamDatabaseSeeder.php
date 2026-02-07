<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;

class IamDatabaseSeeder extends Seeder
{
    /**
     * 运行 IAM 基础数据填充
     *
     * 按顺序执行：
     * 1. 权限初始化
     * 2. 角色初始化（依赖权限）
     * 3. 管理员用户创建（依赖角色）
     * 4. 菜单初始化
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
