<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class UninstallCommand extends Command
{
    protected $signature = 'iam:uninstall
                            {--force : 跳过确认直接执行}
                            {--keep-tables : 保留数据库表}';

    protected $description = '安全卸载 Laravel IAM 扩展包';

    public function handle(): int
    {
        $this->info('🗑️  准备卸载 Laravel IAM...');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('确定要卸载 Laravel IAM 吗?')) {
            $this->info('已取消卸载');

            return self::SUCCESS;
        }

        // 1. 清理应用缓存
        $this->info('正在清理应用缓存...');
        $this->callSilently('cache:clear');
        $this->callSilently('config:clear');
        $this->callSilently('route:clear');
        $this->callSilently('view:clear');
        $this->line('  ✓ 应用缓存已清理');

        // 2. 清理 bootstrap 缓存
        $this->info('正在清理 bootstrap 缓存...');
        $bootstrapCachePath = base_path('bootstrap/cache');
        $cacheFiles = ['packages.php', 'services.php', 'config.php'];

        foreach ($cacheFiles as $file) {
            $filePath = $bootstrapCachePath . '/' . $file;
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
        $this->line('  ✓ Bootstrap 缓存已清理');

        // 3. 清理 IAM 菜单缓存
        $this->info('正在清理 IAM 菜单缓存...');
        try {
            app(\WeiJuKeJi\LaravelIam\Services\MenuService::class)->flushCache();
            $this->line('  ✓ 菜单缓存已清理');
        } catch (\Throwable) {
            $this->line('  - 菜单缓存跳过（可能已清理）');
        }

        // 4. 提示数据库表处理
        if (! $this->option('keep-tables')) {
            $this->newLine();
            $this->warn('⚠️  数据库表提示:');
            $this->line('  以下表由 IAM 创建，如需删除请手动执行回滚:');

            $tables = ConfigHelper::getTables();
            foreach ($tables as $table) {
                $this->line("  - {$table}");
            }

            $this->newLine();
            $this->line('  回滚命令: php artisan migrate:rollback --path=vendor/weijukeji/laravel-iam/database/migrations');
        }

        // 5. 显示后续操作
        $this->newLine();
        $this->info('✅ Laravel IAM 已准备好卸载!');
        $this->newLine();
        $this->warn('📋 现在请运行以下命令完成卸载:');
        $this->newLine();
        $this->line('  <fg=cyan>composer remove weijukeji/laravel-iam --no-scripts</>');
        $this->line('  <fg=cyan>php artisan package:discover --ansi</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
