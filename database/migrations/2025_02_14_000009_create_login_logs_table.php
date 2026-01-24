<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = ConfigHelper::table('login_logs');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->comment('用户ID');
            $table->string('username', 60)->nullable()->comment('用户名');
            $table->string('account', 150)->nullable()->comment('登录账号（邮箱/用户名/手机号）');
            $table->enum('status', ['success', 'failed'])->comment('登录状态');
            $table->string('failure_reason')->nullable()->comment('失败原因');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->text('user_agent')->nullable()->comment('User-Agent');
            $table->string('login_type', 20)->default('password')->comment('登录方式');
            $table->jsonb('metadata')->nullable()->comment('扩展信息');
            $table->timestamp('login_at')->useCurrent()->comment('登录时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('username');
            $table->index('status');
            $table->index('ip');
            $table->index('login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ConfigHelper::table('login_logs'));
    }
};
