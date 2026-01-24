<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

return new class extends Migration
{
    public function up(): void
    {
        $departments = ConfigHelper::table('departments');

        // 第一步：添加字段（username 先设为 nullable）
        Schema::table('users', function (Blueprint $table) use ($departments) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 60)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 32)->default('active')->after('password')->comment('账户状态: active/inactive');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('status');
            }

            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 32)->default('internal')->after('phone')->comment('用户类型');
            }

            if (! Schema::hasColumn('users', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('user_type')->comment('扩展配置');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('metadata')
                    ->constrained($departments)
                    ->nullOnDelete()
                    ->comment('所属部门');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('department_id');
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 第二步：为现有用户填充 username（使用 email 前缀，兼容 MySQL 和 PostgreSQL）
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("UPDATE users SET username = SPLIT_PART(email, '@', 1) WHERE username IS NULL");
        } else {
            // MySQL / MariaDB / SQLite
            DB::statement("UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL");
        }

        // 第三步：处理重复的 username（添加数字后缀）
        $duplicates = DB::select("
            SELECT username, COUNT(*) as cnt
            FROM users
            WHERE username IS NOT NULL
            GROUP BY username
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            $users = DB::table('users')
                ->where('username', $dup->username)
                ->orderBy('id')
                ->get();

            $counter = 1;
            foreach ($users as $user) {
                if ($counter > 1) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $dup->username . $counter]);
                }
                $counter++;
            }
        }

        // 第四步：设置 username 为 NOT NULL 和 UNIQUE
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable(false)->unique()->change();
        });

        // 第五步：添加索引，提升查询性能
        Schema::table('users', function (Blueprint $table) {
            // status 字段经常用于筛选
            if (Schema::hasColumn('users', 'status')) {
                $table->index('status', 'users_status_index');
            }

            // user_type 用于用户类型筛选
            if (Schema::hasColumn('users', 'user_type')) {
                $table->index('user_type', 'users_user_type_index');
            }

            // department_id 用于部门筛选
            if (Schema::hasColumn('users', 'department_id')) {
                $table->index('department_id', 'users_department_id_index');
            }

            // last_login_at 用于排序和筛选
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->index('last_login_at', 'users_last_login_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 删除索引
            if (Schema::hasColumn('users', 'status')) {
                $table->dropIndex('users_status_index');
            }

            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropIndex('users_user_type_index');
            }

            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropIndex('users_department_id_index');
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex('users_last_login_at_index');
            }

            // 删除外键和字段
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }

            // 删除字段
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropColumn('user_type');
            }

            if (Schema::hasColumn('users', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }

            if (Schema::hasColumn('users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }

            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
