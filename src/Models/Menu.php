<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'is_public',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_enabled' => 'boolean',
        'is_public' => 'boolean',
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
     * 根据角色过滤菜单。
     */
    public function isVisibleFor(array $roles, array $permissions): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        // super-admin 角色可以看到所有启用的菜单
        if (in_array('super-admin', $roles)) {
            return true;
        }

        // 公共菜单对所有登录用户可见
        if ($this->is_public) {
            return true;
        }

        // 如果菜单关联了角色，检查用户是否拥有这些角色
        if ($this->roles->isNotEmpty()) {
            return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
        }

        // 菜单没有关联任何角色，且不是公共菜单，默认拒绝访问
        return false;
    }
}
