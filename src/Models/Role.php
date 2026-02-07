<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role as BaseRole;
use WeiJuKeJi\LaravelIam\Models\Menu;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class Role extends BaseRole
{
    use HasFactory;
    use Filterable;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'group',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * 构造函数，动态设置表名
     */
    public function __construct(array $attributes = [])
    {
        $this->table = ConfigHelper::table('roles');
        parent::__construct($attributes);
    }

    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\RoleFilter::class);
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, ConfigHelper::table('menu_role'))->withTimestamps();
    }

    /**
     * Ensure a valid user model is resolved even if the guard is missing.
     */
    public function users(): BelongsToMany
    {
        $model = $this->resolveUserModel();

        if (! $model) {
            throw new \LogicException(
                'Unable to resolve user model for role. ' .
                'Please check your auth.php configuration or set iam.models.user in config/iam.php'
            );
        }

        return $this->morphedByMany(
            $model,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * 解析用户模型类
     */
    protected function resolveUserModel(): ?string
    {
        // 1. 尝试从当前角色的 guard 获取模型
        if ($this->guard_name && $model = getModelForGuard($this->guard_name)) {
            return $model;
        }

        // 2. 尝试从默认 guard 获取模型
        if ($defaultGuard = config('auth.defaults.guard')) {
            if ($model = getModelForGuard($defaultGuard)) {
                return $model;
            }
        }

        // 3. 尝试从 IAM 配置获取用户模型
        if ($model = config('iam.models.user')) {
            return $model;
        }

        // 4. 最后尝试从 auth 配置获取
        return config('auth.providers.users.model');
    }
}
