<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WeiJuKeJi\LaravelIam\Models\Menu;
use Throwable;

class ExportMenusCommand extends Command
{
    protected $signature = 'iam:menus:export {path? : 输出 JSON 文件路径，默认写入模块种子文件}';

    protected $description = '将 menus 表中的菜单树导出为前端路由 JSON 文件';

    public function handle(): int
    {
        $outputPath = $this->argument('path') ?? module_path('Iam', 'database/seeders/menu.routes.json');

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

        return self::SUCCESS;
    }

    protected function transformMenu(Menu $menu): array
    {
        $data = [
            'path' => $menu->path,
            'name' => $menu->name,
            'component' => $menu->component,
            'redirect' => $menu->redirect,
            'sort_order' => $menu->sort_order,
            'is_enabled' => (bool) $menu->is_enabled,
        ];

        if (! is_null($menu->meta)) {
            $data['meta'] = $menu->meta;
        }

        if (! is_null($menu->guard)) {
            $data['guard'] = $menu->guard;
        }

        $children = $menu->children->map(fn (Menu $child) => $this->transformMenu($child))->all();

        $data['children'] = $children;

        return $data;
    }
}
