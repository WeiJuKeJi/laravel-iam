<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Services\MenuService;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // 优先使用项目中的自定义菜单配置
        $path = database_path('seeders/menu.routes.json');

        // 如果项目中没有，使用包内默认配置
        if (! File::exists($path)) {
            $path = __DIR__.'/menu.routes.json';
        }

        // 如果都没有，跳过
        if (! File::exists($path)) {
            $this->command->info('未找到 menu.routes.json，跳过菜单初始化');

            return;
        }

        $menuData = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->seedTree($menuData, null);

        app(MenuService::class)->flushCache();

        $this->command->info('菜单数据已初始化');
    }

    protected function seedTree(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $index => $node) {
            $meta = $node['meta'] ?? [];

            $menu = Menu::updateOrCreate(
                ['name' => $node['name']],
                [
                    'parent_id' => $parentId,
                    'path' => $node['path'] ?? '',
                    'component' => $node['component'] ?? null,
                    'redirect' => $node['redirect'] ?? null,
                    'sort_order' => $node['sort_order'] ?? $index,
                    'is_enabled' => $node['is_enabled'] ?? true,
                    'meta' => $meta,
                ]
            );

            if (! empty($node['children'])) {
                $this->seedTree($node['children'], $menu->id);
            }
        }
    }
}
