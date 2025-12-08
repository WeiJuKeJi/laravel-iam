<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    use HasFactory;
    use Filterable;

    protected $table = 'iam_roles';

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

    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\RoleFilter::class);
    }
}
