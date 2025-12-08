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
            ['name' => 'iam.menus.view', 'display_name' => '查看菜单', 'group' => '菜单管理'],
            ['name' => 'iam.menus.manage', 'display_name' => '管理菜单', 'group' => '菜单管理'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'sanctum'],
                $permission
            );
        }

        $superAdminRole = Role::query()->firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'sanctum'],
            ['display_name' => '超级管理员']
        );

        $systemAdminRole = Role::query()->firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'sanctum'],
            ['display_name' => '系统管理员']
        );

        $editorRole = Role::query()->firstOrCreate(
            ['name' => 'Editor', 'guard_name' => 'sanctum'],
            ['display_name' => '内容编辑']
        );

        $superAdminRole->syncPermissions(Permission::all());
        $systemAdminRole->syncPermissions(Permission::all());

        $admin = User::withTrashed()->where('email', 'admin@settlehub.local')->first();

        if (! $admin) {
            $admin = User::create([
                'name' => '系统管理员',
                'email' => 'admin@settlehub.local',
                'username' => 'admin',
                'password' => Hash::make('Admin@123456'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            if ($admin->trashed()) {
                $admin->restore();
            }

            $admin->forceFill([
                'name' => '系统管理员',
                'username' => 'admin',
                'status' => 'active',
            ])->save();
        }

        $admin->assignRole([$superAdminRole->name, $systemAdminRole->name]);

        if (! $admin->remember_token) {
            $admin->forceFill(['remember_token' => Str::random(10)])->save();
        }

        $this->call(MenuSeeder::class);
    }
}
