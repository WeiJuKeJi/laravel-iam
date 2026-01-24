<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use Filterable;

    protected $table = 'users';

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'status',
        'user_type',
        'phone',
        'department_id',
        'metadata',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'user_type' => 'default',
    ];

    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\UserFilter::class);
    }

    public function setPasswordAttribute($value): void
    {
        if (is_null($value)) {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 查询指定类型的用户
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * 检查用户是否为指定类型
     */
    public function isType(string $type): bool
    {
        return $this->user_type === $type;
    }

    /**
     * 获取用户类型的显示名称
     */
    public function getUserTypeNameAttribute(): ?string
    {
        $types = config('iam.user_types', []);
        return $types[$this->user_type] ?? $this->user_type;
    }

    /**
     * 所属部门
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
