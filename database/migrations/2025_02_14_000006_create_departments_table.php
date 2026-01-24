<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

return new class extends Migration
{
    public function up(): void
    {
        $departments = ConfigHelper::table('departments');
        $users = 'users';

        Schema::create($departments, function (Blueprint $table) use ($users) {
            $table->id();
            $table->nestedSet(); // 添加 _lft, _rgt, parent_id 字段
            $table->string('name', 100)->comment('部门名称');
            $table->string('code', 50)->unique()->comment('部门编码');
            $table->foreignId('manager_id')->nullable()->constrained($users)->nullOnDelete()->comment('部门负责人');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序，数值越小越靠前');
            $table->string('status', 20)->default('active')->comment('状态：active/inactive');
            $table->text('description')->nullable()->comment('部门描述');
            $table->jsonb('metadata')->nullable()->comment('扩展元数据');
            $table->timestamps();

            // 索引
            $table->index('status');
            $table->index('manager_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ConfigHelper::table('departments'));
    }
};
