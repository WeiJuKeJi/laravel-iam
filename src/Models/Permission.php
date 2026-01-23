<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as BasePermission;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class Permission extends BasePermission
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
        $this->table = ConfigHelper::table('permissions');
        parent::__construct($attributes);
    }

    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\PermissionFilter::class);
    }
}
