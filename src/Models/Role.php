<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role as BaseRole;
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

    /**
     * Ensure a valid user model is resolved even if the guard is missing.
     */
    public function users(): BelongsToMany
    {
        $guardName = $this->guard_name ?? config('auth.defaults.guard');
        $model = $guardName ? getModelForGuard($guardName) : null;

        if (! $model) {
            $fallbackGuard = config('auth.defaults.guard');
            $model = $fallbackGuard ? getModelForGuard($fallbackGuard) : null;
        }

        if (! $model) {
            $model = config('auth.providers.users.model');
        }

        return $this->morphedByMany(
            $model,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }
}
