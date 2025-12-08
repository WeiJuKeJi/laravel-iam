<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Collection;
use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Models\User;

class MenuService
{
    protected CacheFactory $cache;

    public function __construct(CacheFactory $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 获取当前用户可见的路由树结构。
     */
    public function getMenuTreeForUser(User $user, bool $forceRefresh = false): array
    {
        $roles = $user->getRoleNames()->sort()->values()->all();
        $permissions = $user->getAllPermissions()->pluck('name')->sort()->values()->all();

        $cacheKey = $this->cacheKey($roles, $permissions);

        if ($forceRefresh) {
            $this->forgetCacheKey($cacheKey);
        }

        return $this->rememberWithTags($cacheKey, function () use ($roles, $permissions) {
            $menus = Menu::query()
                ->with(['roles:id,name', 'permissions:id,name'])
                ->where('is_enabled', true)
                ->get();

            $tree = Menu::buildTree($menus);
            $filtered = $this->filterTree($tree, $roles, $permissions);

            $list = $filtered->map(fn (Menu $menu) => $menu->toFrontendArray())->all();
            $version = $this->generateVersion($menus);

            return [
                'list' => $list,
                'version' => $version,
            ];
        });
    }

    /**
     * 清除缓存，供外部在菜单更新时调用。
     */
    public function flushCache(): void
    {
        $store = $this->cache->store();

        if ($store->getStore() instanceof TaggableStore) {
            $store->tags(['menus'])->flush();
        } else {
            $store->flush();
        }
    }

    protected function cacheKey(array $roles, array $permissions): string
    {
        $roleHash = md5(json_encode($roles));
        $permissionHash = md5(json_encode($permissions));

        return "tree:{$roleHash}:{$permissionHash}";
    }

    protected function forgetCacheKey(string $cacheKey): void
    {
        $store = $this->cache->store();

        if ($store->getStore() instanceof TaggableStore) {
            $store->tags(['menus'])->forget($cacheKey);
        } else {
            $store->forget($cacheKey);
        }
    }

    protected function filterTree(Collection $menus, array $roles, array $permissions): Collection
    {
        return $menus->map(function (Menu $menu) use ($roles, $permissions) {
            $children = $this->filterTree($menu->children, $roles, $permissions);
            $menu->setRelation('children', $children);

            $visible = $menu->isVisibleFor($roles, $permissions);

            if (! $visible && $children->isEmpty()) {
                return null;
            }

            return $menu;
        })->filter()->values();
    }

    protected function rememberWithTags(string $cacheKey, callable $callback, int $ttlMinutes = 30): mixed
    {
        $store = $this->cache->store();

        if ($store->getStore() instanceof TaggableStore) {
            return $store->tags(['menus'])->remember($cacheKey, now()->addMinutes($ttlMinutes), $callback);
        }

        return $store->remember($cacheKey, now()->addMinutes($ttlMinutes), $callback);
    }

    protected function generateVersion(Collection $menus): string
    {
        $latest = $menus->max('updated_at') ?? now();

        return md5($latest->timestamp.$menus->count());
    }
}
