<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use WeiJuKeJi\LaravelIam\Models\Department;
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
            ['name' => 'iam.departments.view', 'display_name' => '查看部门', 'group' => '部门管理'],
            ['name' => 'iam.departments.manage', 'display_name' => '管理部门', 'group' => '部门管理'],
            ['name' => 'iam.login-logs.view', 'display_name' => '查看登录日志', 'group' => '登录日志'],
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

        $ticketManagerRole = Role::query()->firstOrCreate(
            ['name' => 'ticket-manager', 'guard_name' => 'sanctum'],
            ['display_name' => '票务主管']
        );

        $ticketSalesRole = Role::query()->firstOrCreate(
            ['name' => 'ticket-sales', 'guard_name' => 'sanctum'],
            ['display_name' => '售票员']
        );

        $ticketCheckRole = Role::query()->firstOrCreate(
            ['name' => 'ticket-checker', 'guard_name' => 'sanctum'],
            ['display_name' => '检票员']
        );

        $serviceManagerRole = Role::query()->firstOrCreate(
            ['name' => 'service-manager', 'guard_name' => 'sanctum'],
            ['display_name' => '客服主管']
        );

        $serviceStaffRole = Role::query()->firstOrCreate(
            ['name' => 'service-staff', 'guard_name' => 'sanctum'],
            ['display_name' => '客服专员']
        );

        $financeRole = Role::query()->firstOrCreate(
            ['name' => 'finance', 'guard_name' => 'sanctum'],
            ['display_name' => '财务人员']
        );

        // 分配权限
        $superAdminRole->syncPermissions(Permission::all());

        // 创建超级管理员账号
        $admin = User::withTrashed()->where('email', 'admin@scenic.local')->first();

        if (! $admin) {
            $admin = User::create([
                'name' => '系统管理员',
                'email' => 'admin@scenic.local',
                'username' => 'admin',
                'phone' => '13800000000',
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

        $admin->assignRole($superAdminRole->name);

        if (! $admin->remember_token) {
            $admin->forceFill(['remember_token' => Str::random(10)])->save();
        }

        // 先创建部门
        $this->call(DepartmentSeeder::class);

        // 创建示例用户（需要部门数据）
        $this->createSampleUsers($ticketManagerRole, $ticketSalesRole, $ticketCheckRole, $serviceManagerRole, $serviceStaffRole, $financeRole);

        // 最后创建菜单
        $this->call(MenuSeeder::class);
    }

    protected function createSampleUsers(Role ...$roles): void
    {
        [$ticketManagerRole, $ticketSalesRole, $ticketCheckRole, $serviceManagerRole, $serviceStaffRole, $financeRole] = $roles;

        // 获取部门
        $ticketDept = Department::where('code', 'TICKET')->first();
        $salesCenter = Department::where('code', 'TICKET-SALES')->first();
        $checkCenter = Department::where('code', 'TICKET-CHECK')->first();
        $serviceDept = Department::where('code', 'SERVICE')->first();
        $receptionDept = Department::where('code', 'SERVICE-RECEPTION')->first();
        $financeDept = Department::where('code', 'FINANCE')->first();

        // 票务主管
        $this->createOrUpdateUser([
            'name' => '张伟',
            'email' => 'zhangwei@scenic.local',
            'username' => 'zhangwei',
            'phone' => '13800001001',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $ticketDept?->id,
        ], $ticketManagerRole);

        // 售票员
        $this->createOrUpdateUser([
            'name' => '李娜',
            'email' => 'lina@scenic.local',
            'username' => 'lina',
            'phone' => '13800001002',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $salesCenter?->id,
        ], $ticketSalesRole);

        $this->createOrUpdateUser([
            'name' => '王芳',
            'email' => 'wangfang@scenic.local',
            'username' => 'wangfang',
            'phone' => '13800001003',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $salesCenter?->id,
        ], $ticketSalesRole);

        // 检票员
        $this->createOrUpdateUser([
            'name' => '刘强',
            'email' => 'liuqiang@scenic.local',
            'username' => 'liuqiang',
            'phone' => '13800001004',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $checkCenter?->id,
        ], $ticketCheckRole);

        $this->createOrUpdateUser([
            'name' => '陈明',
            'email' => 'chenming@scenic.local',
            'username' => 'chenming',
            'phone' => '13800001005',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $checkCenter?->id,
        ], $ticketCheckRole);

        // 客服主管
        $this->createOrUpdateUser([
            'name' => '赵静',
            'email' => 'zhaojing@scenic.local',
            'username' => 'zhaojing',
            'phone' => '13800001006',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $serviceDept?->id,
        ], $serviceManagerRole);

        // 客服专员
        $this->createOrUpdateUser([
            'name' => '孙丽',
            'email' => 'sunli@scenic.local',
            'username' => 'sunli',
            'phone' => '13800001007',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $receptionDept?->id,
        ], $serviceStaffRole);

        // 财务人员
        $this->createOrUpdateUser([
            'name' => '周建',
            'email' => 'zhoujian@scenic.local',
            'username' => 'zhoujian',
            'phone' => '13800001008',
            'password' => Hash::make('Pass@123456'),
            'status' => 'active',
            'department_id' => $financeDept?->id,
        ], $financeRole);
    }

    protected function createOrUpdateUser(array $userData, Role $role): void
    {
        $user = User::withTrashed()->where('email', $userData['email'])->first();

        if (! $user) {
            $user = User::create($userData);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill([
                'name' => $userData['name'],
                'username' => $userData['username'],
                'phone' => $userData['phone'],
                'status' => $userData['status'],
                'department_id' => $userData['department_id'] ?? null,
            ])->save();
        }

        $user->assignRole($role->name);

        if (! $user->remember_token) {
            $user->forceFill(['remember_token' => Str::random(10)])->save();
        }
    }
}
