<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use WeiJuKeJi\LaravelIam\Database\Seeders\MenuSeeder;
use WeiJuKeJi\LaravelIam\Services\MenuService;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class MenuReseedCommand extends Command
{
    protected $signature = 'iam:menu:reseed
                            {--force : 强制执行，不询问确认}';

    protected $description = '清空并重新填充菜单数据';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('此操作将清空所有菜单数据并重新填充，确定继续吗？')) {
                $this->info('操作已取消');
                return self::SUCCESS;
            }
        }

        $this->info('');
        $this->info('  ╔══════════════════════════════════════╗');
        $this->info('  ║       菜单数据重置中...             ║');
        $this->info('  ╚══════════════════════════════════════╝');
        $this->info('');

        // 1. 清空菜单数据
        $this->clearMenuData();

        // 2. 重新填充
        $this->reseedMenuData();

        // 3. 清理缓存
        $this->clearCache();

        // 4. 显示完成信息
        $this->showCompletionInfo();

        return self::SUCCESS;
    }

    protected function clearMenuData(): void
    {
        $this->info('🗑️  清空现有菜单数据...');

        try {
            DB::beginTransaction();

            // 清空关联表
            DB::table(ConfigHelper::table('menu_role'))->truncate();

            // 清空菜单表
            DB::table(ConfigHelper::table('menus'))->truncate();

            DB::commit();

            $this->line('  ✓ 菜单数据已清空');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('  ✗ 清空失败: '.$e->getMessage());
            exit(self::FAILURE);
        }
    }

    protected function reseedMenuData(): void
    {
        $this->info('🌱 重新填充菜单数据...');

        try {
            $seeder = new MenuSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->line('  ✓ 菜单数据已重新填充');
        } catch (\Exception $e) {
            $this->error('  ✗ 填充失败: '.$e->getMessage());
            exit(self::FAILURE);
        }
    }

    protected function clearCache(): void
    {
        $this->info('🧹 清理菜单缓存...');

        app(MenuService::class)->flushCache();

        $this->line('  ✓ 缓存已清理');
    }

    protected function showCompletionInfo(): void
    {
        $this->newLine();
        $this->info('══════════════════════════════════════════');
        $this->info('  ✅ 菜单数据重置完成!');
        $this->info('══════════════════════════════════════════');
        $this->newLine();

        $menuCount = DB::table(ConfigHelper::table('menus'))->count();
        $this->line("  <fg=cyan>菜单总数:</> {$menuCount}");
        $this->newLine();
    }
}
