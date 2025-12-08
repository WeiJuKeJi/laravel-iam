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
        $path = module_path('Iam', 'database/seeders/menu.routes.json');
        if (! File::exists($path)) {
            return;
        }

        $menuData = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->seedTree($menuData, null);

        app(MenuService::class)->flushCache();
    }

    protected function seedTree(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $index => $node) {
            $meta = $node['meta'] ?? [];
            $guard = $node['guard'] ?? null;

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
                    'guard' => $guard,
                ]
            );

            if (! empty($node['children'])) {
                $this->seedTree($node['children'], $menu->id);
            }
        }
    }
}
