<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;
use WeiJuKeJi\LaravelIam\Models\Menu;

class ExportMenusCommand extends Command
{
    protected $signature = 'iam:menus:export {path? : 输出 JSON 文件路径，默认 database/seeders/menu.routes.json}';

    protected $description = '将 menus 表中的菜单树导出为 JSON 文件，供 MenuSeeder 使用';

    public function handle(): int
    {
        $outputPath = $this->argument('path') ?? database_path('seeders/menu.routes.json');

        $menus = Menu::query()->get();

        if ($menus->isEmpty()) {
            $this->warn('menus 表暂无数据，未生成任何文件。');

            return self::SUCCESS;
        }

        $tree = Menu::buildTree($menus);

        $payload = $tree->map(fn (Menu $menu) => $this->transformMenu($menu))->values()->all();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($json === false) {
            $this->error('JSON 序列化失败：'.json_last_error_msg());

            return self::FAILURE;
        }

        try {
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $json.PHP_EOL);
        } catch (Throwable $exception) {
            $this->error('写入文件失败：'.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('菜单路由 JSON 导出完成。');
        $this->line('输出路径：'.$outputPath);
        $this->newLine();
        $this->line('提示：下次运行 db:seed 时，MenuSeeder 会自动读取此文件。');

        return self::SUCCESS;
    }

    protected function transformMenu(Menu $menu): array
    {
        $data = [
            'name' => $menu->name,
            'path' => $menu->path,
        ];

        if ($menu->component) {
            $data['component'] = $menu->component;
        }

        if ($menu->redirect) {
            $data['redirect'] = $menu->redirect;
        }

        $data['sort_order'] = $menu->sort_order;
        $data['is_enabled'] = (bool) $menu->is_enabled;

        if (! empty($menu->meta)) {
            $data['meta'] = $menu->meta;
        }

        if (! is_null($menu->guard)) {
            $data['guard'] = $menu->guard;
        }

        if ($menu->children->isNotEmpty()) {
            $data['children'] = $menu->children
                ->map(fn (Menu $child) => $this->transformMenu($child))
                ->all();
        }

        return $data;
    }
}
