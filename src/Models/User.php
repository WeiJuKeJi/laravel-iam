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
        'phone',
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
}
