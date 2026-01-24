<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class LoginLog extends Model
{
    use HasFactory;
    use Filterable;

    protected $fillable = [
        'user_id',
        'username',
        'account',
        'status',
        'failure_reason',
        'ip',
        'user_agent',
        'login_type',
        'metadata',
        'login_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'login_at' => 'datetime',
    ];

    /**
     * 构造函数，动态设置表名
     */
    public function __construct(array $attributes = [])
    {
        $this->table = ConfigHelper::table('login_logs');
        parent::__construct($attributes);
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 模型筛选
     */
    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\LoginLogFilter::class);
    }

    /**
     * 查询作用域：成功的登录
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * 查询作用域：失败的登录
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * 记录成功登录
     */
    public static function recordSuccess(
        ?User $user,
        string $account,
        string $ip,
        ?string $userAgent = null,
        string $loginType = 'password',
        array $metadata = []
    ): self {
        return static::create([
            'user_id' => $user?->id,
            'username' => $user?->username,
            'account' => $account,
            'status' => 'success',
            'ip' => $ip,
            'user_agent' => $userAgent,
            'login_type' => $loginType,
            'metadata' => $metadata,
            'login_at' => now(),
        ]);
    }

    /**
     * 记录失败登录
     */
    public static function recordFailure(
        string $account,
        string $reason,
        string $ip,
        ?string $userAgent = null,
        string $loginType = 'password',
        array $metadata = []
    ): self {
        return static::create([
            'account' => $account,
            'status' => 'failed',
            'failure_reason' => $reason,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'login_type' => $loginType,
            'metadata' => $metadata,
            'login_at' => now(),
        ]);
    }
}
