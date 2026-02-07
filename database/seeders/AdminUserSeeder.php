<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use WeiJuKeJi\LaravelIam\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
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

            $this->command->info('管理员账号已创建 (admin@scenic.local / Admin@123456)');
        } else {
            if (method_exists($admin, 'trashed') && $admin->trashed()) {
                $admin->restore();
            }

            $admin->forceFill([
                'name' => '系统管理员',
                'username' => 'admin',
                'status' => 'active',
            ])->save();

            $this->command->info('管理员账号已更新');
        }

        // 分配超级管理员角色
        $admin->assignRole('super-admin');

        if (! $admin->remember_token) {
            $admin->forceFill(['remember_token' => Str::random(10)])->save();
        }
    }

    protected function resolveUserModelClass(): string
    {
        $model = config('iam.models.user', User::class);

        return is_a($model, User::class, true) ? $model : User::class;
    }
}
