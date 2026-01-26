<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Models\User;

class IamDatabaseSeeder extends Seeder
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

        // 创建角色
        $superAdminRole = Role::query()->firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'sanctum'],
            ['display_name' => '超级管理员']
        );

        // 分配权限
        $superAdminRole->syncPermissions(Permission::all());

        // 创建超级管理员账号
        $userModel = $this->resolveUserModelClass();
        $adminQuery = method_exists($userModel, 'withTrashed')
            ? $userModel::withTrashed()
            : $userModel::query();

        $admin = $adminQuery->where('email', 'admin@scenic.local')->first();

        if (! $admin) {
            $admin = $userModel::create([
                'name' => '系统管理员',
                'email' => 'admin@scenic.local',
                'username' => 'admin',
                'phone' => '13800000000',
                'password' => Hash::make('Admin@123456'),
                'status' => 'active',
                'user_type' => 'internal',
                'email_verified_at' => now(),
            ]);
        } else {
            if (method_exists($admin, 'trashed') && $admin->trashed()) {
                $admin->restore();
            }

            $admin->forceFill([
                'name' => '系统管理员',
                'username' => 'admin',
                'status' => 'active',
            ])->save();
        }

        $admin->assignRole($superAdminRole->name);

        if (! $admin->remember_token) {
            $admin->forceFill(['remember_token' => Str::random(10)])->save();
        }

        // 最后创建菜单
        $this->call(MenuSeeder::class);
    }

    protected function resolveUserModelClass(): string
    {
        $model = config('iam.models.user', User::class);

        return is_a($model, User::class, true) ? $model : User::class;
    }
}
