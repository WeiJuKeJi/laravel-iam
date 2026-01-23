<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class Menu extends Model
{
    use HasFactory, Filterable;

    /**
     * 构造函数，动态设置表名
     */
    public function __construct(array $attributes = [])
    {
        $this->table = ConfigHelper::table('menus');
        parent::__construct($attributes);
    }

    protected static function booted(): void
    {
        $flush = function (): void {
            app(\WeiJuKeJi\LaravelIam\Services\MenuService::class)->flushCache();
        };

        static::saved(function () use ($flush): void {
            $flush();
        });

        static::deleted(function () use ($flush): void {
            $flush();
        });

    }

    protected $fillable = [
        'parent_id',
        'name',
        'path',
        'component',
        'redirect',
        'sort_order',
        'is_enabled',
        'meta',
        'guard',
    ];

    protected $casts = [
        'meta' => 'array',
        'guard' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\MenuFilter::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, ConfigHelper::table('menu_role'))->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, ConfigHelper::table('menu_permission'))->withTimestamps();
    }

    /**
     * 递归构建树状结构。
     */
    public static function buildTree(Collection $menus): Collection
    {
        $menus = $menus->sortBy(function (Menu $menu) {
            $parent = $menu->parent_id ?? 0;

            return sprintf('%010d:%010d:%010d', $parent, $menu->sort_order, $menu->id);
        })->values();

        $grouped = $menus->groupBy('parent_id');

        $build = function ($parentId) use (&$build, $grouped) {
            return ($grouped[$parentId] ?? collect())->map(function (Menu $menu) use (&$build) {
                $menu->setRelation('children', $build($menu->getKey()));

                return $menu;
            })->values();
        };

        return $build(null);
    }

    /**
     * 合并 meta 信息，写入前处理空数组。
     */
    public function setMetaAttribute($value): void
    {
        $this->attributes['meta'] = empty($value) ? null : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setGuardAttribute($value): void
    {
        $this->attributes['guard'] = empty($value) ? null : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function toFrontendArray(): array
    {
        $data = [
            'path' => $this->path,
            'name' => $this->name,
            'component' => $this->component,
        ];

        if ($this->redirect) {
            $data['redirect'] = $this->redirect;
        }

        $meta = $this->meta ?? [];

        if ($this->guard) {
            $meta['guard'] = $this->guard;
        }

        if (! empty($meta)) {
            $data['meta'] = $meta;
        }

        $children = $this->children->map(fn (Menu $child) => $child->toFrontendArray())->all();

        if (! empty($children)) {
            $data['children'] = $children;
        }

        return $data;
    }

    /**
     * 根据角色和权限过滤菜单。
     */
    public function isVisibleFor(array $roles, array $permissions): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->roles->isNotEmpty() && ! $this->roles->pluck('name')->intersect($roles)->isNotEmpty()) {
            return false;
        }

        if ($this->permissions->isNotEmpty() && ! $this->permissions->pluck('name')->intersect($permissions)->isNotEmpty()) {
            return false;
        }

        $guard = $this->guard ?? [];

        if (empty($guard)) {
            return true;
        }

        if (Arr::isAssoc($guard)) {
            $targetRoles = Arr::get($guard, 'role', []);
            $mode = Arr::get($guard, 'mode', 'include');

            if ($mode === 'include') {
                return empty($targetRoles) || ! empty(array_intersect($roles, $targetRoles));
            }

            if ($mode === 'except') {
                return empty(array_intersect($roles, $targetRoles));
            }
        }

        if (is_array($guard) && ! Arr::isAssoc($guard)) {
            return ! empty(array_intersect($roles, $guard));
        }

        return true;
    }
}
