<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

return new class extends Migration {
    public function up(): void
    {
        $menus = ConfigHelper::table('menus');
        $menuRole = ConfigHelper::table('menu_role');
        $menuPermission = ConfigHelper::table('menu_permission');
        $roles = ConfigHelper::table('roles');
        $permissions = ConfigHelper::table('permissions');

        Schema::create($menus, function (Blueprint $table) use ($menus) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained($menus)
                ->nullOnDelete();
            $table->string('name')->unique()->comment('路由名称，需与前端 name 对应');
            $table->string('path')->comment('前端路由路径');
            $table->string('component')->nullable()->comment('前端组件路径或别名，布局路由可为空');
            $table->string('redirect')->nullable()->comment('重定向地址');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序，数值越小越靠前');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('meta')->nullable()->comment('前端路由元信息');
            $table->json('guard')->nullable()->comment('额外守卫配置，支持角色/权限白名单或黑名单');
            $table->timestamps();

            // 索引：parent_id 用于树形结构查询
            $table->index('parent_id', substr($menus . '_parent_id_index', 0, 64));
            // 索引：is_enabled 用于筛选启用的菜单
            $table->index('is_enabled', substr($menus . '_is_enabled_index', 0, 64));
            // 索引：sort_order 用于排序
            $table->index('sort_order', substr($menus . '_sort_order_index', 0, 64));
            // 复合索引：常用的查询组合
            $table->index(['parent_id', 'is_enabled', 'sort_order'], substr($menus . '_parent_enabled_sort_index', 0, 64));
        });

        Schema::create($menuRole, function (Blueprint $table) use ($menus, $roles) {
            $table->id();
            $table->foreignId('menu_id')->constrained($menus)->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'role_id']);
        });

        Schema::create($menuPermission, function (Blueprint $table) use ($menus, $permissions) {
            $table->id();
            $table->foreignId('menu_id')->constrained($menus)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ConfigHelper::table('menu_permission'));
        Schema::dropIfExists(ConfigHelper::table('menu_role'));
        Schema::dropIfExists(ConfigHelper::table('menus'));
    }
};
