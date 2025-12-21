<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'iam:install
                            {--force : 覆盖已存在的配置文件}
                            {--seed : 运行数据库填充}
                            {--no-migrate : 跳过数据库迁移}
                            {--sync-permissions : 同步路由权限}';

    protected $description = '安装并初始化 Laravel IAM 扩展包';

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════╗');
        $this->info('  ║       Laravel IAM 安装向导           ║');
        $this->info('  ╚══════════════════════════════════════╝');
        $this->info('');

        // 1. 发布配置文件
        $this->publishConfig();

        // 2. 发布迁移文件
        $this->publishMigrations();

        // 3. 运行数据库迁移
        if (! $this->option('no-migrate')) {
            $this->runMigrations();
        }

        // 4. 运行数据填充
        if ($this->option('seed')) {
            $this->runSeeders();
        }

        // 5. 同步权限
        if ($this->option('sync-permissions')) {
            $this->syncPermissions();
        }

        // 6. 清理缓存
        $this->clearCache();

        // 7. 显示完成信息
        $this->showCompletionInfo();

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->info('📄 发布配置文件...');

        $params = ['--provider' => 'WeiJuKeJi\LaravelIam\IamServiceProvider', '--tag' => 'iam-config'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->callSilently('vendor:publish', $params);

        $configPath = config_path('iam.php');
        if (file_exists($configPath)) {
            $this->line('  ✓ 配置文件已发布: config/iam.php');
        } else {
            $this->line('  - 配置文件已存在，跳过（使用 --force 覆盖）');
        }
    }

    protected function publishMigrations(): void
    {
        $this->info('📦 发布迁移文件...');

        $this->callSilently('vendor:publish', [
            '--provider' => 'WeiJuKeJi\LaravelIam\IamServiceProvider',
            '--tag' => 'iam-migrations',
        ]);

        $this->line('  ✓ 迁移文件已发布到 database/migrations/');
    }

    protected function runMigrations(): void
    {
        $this->info('🗃️  运行数据库迁移...');

        $this->call('migrate', ['--force' => $this->getLaravel()->environment() === 'production']);

        $this->line('  ✓ 数据库迁移完成');
    }

    protected function runSeeders(): void
    {
        $this->info('🌱 运行数据填充...');

        $this->call('db:seed', [
            '--class' => 'WeiJuKeJi\LaravelIam\Database\Seeders\IamDatabaseSeeder',
            '--force' => $this->getLaravel()->environment() === 'production',
        ]);

        $this->line('  ✓ 数据填充完成');
    }

    protected function syncPermissions(): void
    {
        $this->info('🔑 同步路由权限...');

        $this->call('iam:sync-permissions');

        $this->line('  ✓ 权限同步完成');
    }

    protected function clearCache(): void
    {
        $this->info('🧹 清理缓存...');

        $this->callSilently('config:clear');
        $this->callSilently('route:clear');
        $this->callSilently('cache:clear');

        $this->line('  ✓ 缓存已清理');
    }

    protected function showCompletionInfo(): void
    {
        $this->newLine();
        $this->info('══════════════════════════════════════════');
        $this->info('  ✅ Laravel IAM 安装完成!');
        $this->info('══════════════════════════════════════════');
        $this->newLine();

        $this->line('  <fg=cyan>默认管理员账号:</> admin@settlehub.local');
        $this->line('  <fg=cyan>默认密码:</> Admin@123456');
        $this->newLine();

        $this->line('  <fg=yellow>下一步操作:</>');
        $this->line('  1. 修改 config/iam.php 中的配置');
        $this->line('  2. 如未运行 seed，执行: php artisan iam:install --seed');
        $this->line('  3. 同步权限: php artisan iam:sync-permissions');
        $this->newLine();

        $this->line('  <fg=yellow>可用命令:</>');
        $this->line('  • iam:install          - 安装扩展包');
        $this->line('  • iam:sync-permissions - 同步路由权限');
        $this->line('  • iam:menus:export     - 导出菜单数据');
        $this->line('  • iam:menu:reseed      - 重置菜单数据');
        $this->line('  • iam:uninstall        - 卸载扩展包');
        $this->newLine();
    }
}
