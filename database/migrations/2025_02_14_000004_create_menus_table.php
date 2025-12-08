<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('iam_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('iam_menus')
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
        });

        Schema::create('iam_menu_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('iam_menus')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('iam_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'role_id']);
        });

        Schema::create('iam_menu_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('iam_menus')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('iam_permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iam_menu_permission');
        Schema::dropIfExists('iam_menu_role');
        Schema::dropIfExists('iam_menus');
    }
};
