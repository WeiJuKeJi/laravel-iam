<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Collection;
use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Models\User;

class MenuService
{
    /**
     * 缓存键前缀
     */
    protected const CACHE_PREFIX = 'iam_menu_';

    /**
     * 缓存键列表的键名
     */
    protected const CACHE_KEYS_LIST = 'iam_menu_cache_keys';

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

        $cacheKey = $this->cacheKey($roles);

        if ($forceRefresh) {
            $this->forgetCacheKey($cacheKey);
        }

        return $this->rememberWithTags($cacheKey, function () use ($roles) {
            $menus = Menu::query()
                ->with(['roles:id,name'])
                ->where('is_enabled', true)
                ->get();

            $tree = Menu::buildTree($menus);
            $filtered = $this->filterTree($tree, $roles);

            $list = $filtered->map(fn (Menu $menu) => $menu->toFrontendArray())->all();
            $version = $this->generateVersion($menus);

            return [
                'list' => $list,
                'version' => $version,
            ];
        });
    }

    /**
     * 清除菜单缓存，供外部在菜单更新时调用。
     * 使用安全的缓存清除策略，避免清空整个应用缓存。
     */
    public function flushCache(): void
    {
        $store = $this->cache->store();

        if ($store->getStore() instanceof TaggableStore) {
            // 支持标签的缓存驱动，直接按标签清除
            $store->tags(['menus'])->flush();
        } else {
            // 不支持标签的缓存驱动，逐个删除已记录的缓存键
            $cacheKeys = $store->get(self::CACHE_KEYS_LIST, []);
            foreach ($cacheKeys as $key) {
                $store->forget($key);
            }
            $store->forget(self::CACHE_KEYS_LIST);
        }
    }

    protected function cacheKey(array $roles): string
    {
        $roleHash = md5(json_encode($roles));

        return self::CACHE_PREFIX . "tree:{$roleHash}";
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

    protected function filterTree(Collection $menus, array $roles): Collection
    {
        return $menus->map(function (Menu $menu) use ($roles) {
            $children = $this->filterTree($menu->children, $roles);
            $menu->setRelation('children', $children);

            $visible = $menu->isVisibleFor($roles, []);

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

        // 记录缓存键以便后续清除
        $this->trackCacheKey($cacheKey);

        return $store->remember($cacheKey, now()->addMinutes($ttlMinutes), $callback);
    }

    /**
     * 记录缓存键，用于不支持标签的缓存驱动。
     */
    protected function trackCacheKey(string $cacheKey): void
    {
        $store = $this->cache->store();
        $cacheKeys = $store->get(self::CACHE_KEYS_LIST, []);

        if (! in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            $store->put(self::CACHE_KEYS_LIST, $cacheKeys, now()->addDays(7));
        }
    }

    protected function generateVersion(Collection $menus): string
    {
        $latest = $menus->max('updated_at') ?? now();

        return md5($latest->timestamp.$menus->count());
    }
}
