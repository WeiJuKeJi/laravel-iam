<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 为常用查询字段添加索引，提升查询性能。
     */
    public function up(): void
    {
        // 用户表索引
        Schema::table('users', function (Blueprint $table) {
            // status 字段经常用于筛选
            if (Schema::hasColumn('users', 'status')) {
                $table->index('status', 'users_status_index');
            }

            // last_login_at 用于排序和筛选
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->index('last_login_at', 'users_last_login_at_index');
            }
        });

        // 菜单表索引
        Schema::table('iam_menus', function (Blueprint $table) {
            // parent_id 用于树形结构查询
            if (! $this->hasIndex('iam_menus', 'iam_menus_parent_id_index')) {
                $table->index('parent_id', 'iam_menus_parent_id_index');
            }

            // is_enabled 用于筛选启用的菜单
            if (! $this->hasIndex('iam_menus', 'iam_menus_is_enabled_index')) {
                $table->index('is_enabled', 'iam_menus_is_enabled_index');
            }

            // sort_order 用于排序
            if (! $this->hasIndex('iam_menus', 'iam_menus_sort_order_index')) {
                $table->index('sort_order', 'iam_menus_sort_order_index');
            }

            // 复合索引：常用的查询组合
            if (! $this->hasIndex('iam_menus', 'iam_menus_parent_enabled_sort_index')) {
                $table->index(['parent_id', 'is_enabled', 'sort_order'], 'iam_menus_parent_enabled_sort_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_index');
            $table->dropIndex('users_last_login_at_index');
        });

        Schema::table('iam_menus', function (Blueprint $table) {
            $table->dropIndex('iam_menus_parent_id_index');
            $table->dropIndex('iam_menus_is_enabled_index');
            $table->dropIndex('iam_menus_sort_order_index');
            $table->dropIndex('iam_menus_parent_enabled_sort_index');
        });
    }

    /**
     * 检查索引是否已存在
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $result = $connection->select(
                "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
        } elseif ($driver === 'mysql') {
            $result = $connection->select(
                "SHOW INDEX FROM {$table} WHERE Key_name = ?",
                [$indexName]
            );
        } else {
            // SQLite 或其他数据库，尝试创建索引
            return false;
        }

        return count($result) > 0;
    }
};
