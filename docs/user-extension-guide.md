# 用户模型扩展指南

当你的项目使用 Laravel IAM 扩展包时，可能需要为用户添加项目特定的字段，例如销售渠道、商户 ID 等。本文档介绍了几种扩展用户模型的方式。

## 方案选择

| 方案 | 适用场景 | 优点 | 缺点 |
|------|---------|------|------|
| 方案 1：使用 metadata 字段 | 字段少（<5个），无需复杂查询 | 无需修改表结构，快速实现 | 不便于查询和索引 |
| 方案 2：继承 User 模型 | 需要多个字段，需要查询和索引 | 类型安全，支持查询和索引 | 需要创建迁移 |
| 方案 3：创建关联表 | 字段很多，需要灵活扩展 | 解耦，易于维护 | 额外的表和查询 |

---

## 方案 1：使用 metadata 字段（推荐用于简单场景）

Laravel IAM 的 User 模型自带 `metadata` 字段（jsonb 类型），可以直接存储项目特定的数据。

### 1.1 在配置中声明字段结构

编辑 `config/iam.php`，声明你的自定义字段：

```php
'user_metadata_schema' => [
    'sales_channel' => [
        'type' => 'string',
        'description' => '销售渠道：online/offline',
        'required' => false,
    ],
    'merchant_id' => [
        'type' => 'integer',
        'description' => '所属商户ID',
        'required' => false,
    ],
    'department' => [
        'type' => 'string',
        'description' => '所属部门',
        'required' => false,
    ],
],
```

### 1.2 使用 metadata

```php
use WeiJuKeJi\LaravelIam\Models\User;

// 创建用户时设置 metadata
$user = User::create([
    'name' => '张三',
    'email' => 'zhangsan@example.com',
    'username' => 'zhangsan',
    'password' => 'password',
    'metadata' => [
        'sales_channel' => 'online',
        'merchant_id' => 123,
        'department' => '销售部',
    ],
]);

// 读取 metadata
$channel = $user->metadata['sales_channel'] ?? null;

// 更新 metadata
$user->update([
    'metadata' => array_merge($user->metadata ?? [], [
        'sales_channel' => 'offline',
    ]),
]);
```

### 1.3 查询 metadata（PostgreSQL）

如果你使用 PostgreSQL，可以直接查询 jsonb 字段：

```php
// 查询指定销售渠道的用户
$users = User::whereRaw("metadata->>'sales_channel' = ?", ['online'])->get();

// 查询指定商户的用户
$users = User::whereRaw("metadata->>'merchant_id' = ?", [123])->get();
```

### 1.4 优缺点

**优点**：
- 无需修改数据库表结构
- 灵活性高，随时可以添加新字段
- 适合快速开发

**缺点**：
- 查询性能相对较低（可以通过 GIN 索引优化）
- 缺乏强类型约束
- MySQL 的 JSON 查询能力有限

---

## 方案 2：继承 User 模型（推荐用于复杂场景）

创建自己的 User 模型并继承 IAM 的 User 模型，这样可以添加自定义字段和方法。

### 2.1 创建自定义 User 模型

创建 `app/Models/User.php`：

```php
<?php

namespace App\Models;

use WeiJuKeJi\LaravelIam\Models\User as BaseUser;

class User extends BaseUser
{
    // 添加自定义字段到 fillable
    protected $fillable = [
        ...parent::$fillable,
        'sales_channel',
        'merchant_id',
        'department',
    ];

    // 添加自定义 casts
    protected $casts = [
        ...parent::$casts,
        'merchant_id' => 'integer',
    ];

    // 添加关联关系
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    // 添加自定义方法
    public function isOnlineChannel(): bool
    {
        return $this->sales_channel === 'online';
    }

    // 添加查询作用域
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('sales_channel', $channel);
    }

    public function scopeByMerchant($query, int $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }
}
```

### 2.2 创建数据库迁移

创建迁移文件添加自定义字段：

```bash
php artisan make:migration add_custom_fields_to_users_table
```

编辑迁移文件：

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sales_channel', 32)
                ->nullable()
                ->after('status')
                ->comment('销售渠道：online/offline');

            $table->unsignedBigInteger('merchant_id')
                ->nullable()
                ->after('sales_channel')
                ->comment('所属商户ID');

            $table->string('department', 100)
                ->nullable()
                ->after('merchant_id')
                ->comment('所属部门');

            // 添加索引
            $table->index('sales_channel');
            $table->index('merchant_id');

            // 如果有外键关系
            // $table->foreign('merchant_id')
            //     ->references('id')
            //     ->on('merchants')
            //     ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sales_channel', 'merchant_id', 'department']);
        });
    }
};
```

运行迁移：

```bash
php artisan migrate
```

### 2.3 配置模型绑定

编辑 `config/iam.php`，将默认的 User 模型替换为你的自定义模型：

```php
'models' => [
    'user' => \App\Models\User::class,
    'menu' => \WeiJuKeJi\LaravelIam\Models\Menu::class,
],
```

### 2.4 更新认证配置

编辑 `config/auth.php`：

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class, // 使用你的自定义模型
    ],
],
```

### 2.5 使用自定义模型

```php
use App\Models\User;

// 使用自定义字段
$user = User::create([
    'name' => '李四',
    'email' => 'lisi@example.com',
    'username' => 'lisi',
    'password' => 'password',
    'sales_channel' => 'online',
    'merchant_id' => 123,
    'department' => '销售部',
]);

// 使用自定义方法
if ($user->isOnlineChannel()) {
    // ...
}

// 使用查询作用域
$onlineUsers = User::byChannel('online')->get();
$merchantUsers = User::byMerchant(123)->get();

// 使用关联关系
$merchant = $user->merchant;
```

### 2.6 优缺点

**优点**：
- 类型安全，IDE 自动完成
- 支持数据库索引，查询性能好
- 可以添加关联关系和业务方法
- 数据验证更方便

**缺点**：
- 需要创建和维护迁移文件
- 修改表结构需要重新部署

---

## 方案 3：创建用户配置关联表

如果用户的扩展信息很多且经常变化，可以创建独立的配置表。

### 3.1 创建 UserProfile 模型

```bash
php artisan make:model UserProfile -m
```

编辑模型 `app/Models/UserProfile.php`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'sales_channel',
        'merchant_id',
        'department',
        'region',
        'level',
        // 更多自定义字段...
    ];

    protected $casts = [
        'merchant_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
```

### 3.2 创建迁移

编辑迁移文件：

```php
public function up(): void
{
    Schema::create('user_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('sales_channel', 32)->nullable();
        $table->unsignedBigInteger('merchant_id')->nullable();
        $table->string('department', 100)->nullable();
        $table->string('region', 100)->nullable();
        $table->string('level', 32)->nullable();
        $table->timestamps();

        $table->index('sales_channel');
        $table->index('merchant_id');
    });
}
```

### 3.3 在 User 模型中添加关联

编辑 `app/Models/User.php`：

```php
class User extends \WeiJuKeJi\LaravelIam\Models\User
{
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // 便捷访问方法
    public function getSalesChannelAttribute()
    {
        return $this->profile?->sales_channel;
    }

    public function getMerchantIdAttribute()
    {
        return $this->profile?->merchant_id;
    }
}
```

### 3.4 使用关联表

```php
// 创建用户及配置
$user = User::create([...]);
$user->profile()->create([
    'sales_channel' => 'online',
    'merchant_id' => 123,
    'department' => '销售部',
]);

// 预加载查询
$users = User::with('profile')->get();

// 通过配置查询用户
$users = User::whereHas('profile', function ($query) {
    $query->where('sales_channel', 'online');
})->get();
```

### 3.5 优缺点

**优点**：
- 主表结构保持简洁
- 扩展字段与核心字段解耦
- 易于维护和扩展

**缺点**：
- 额外的表和关联查询
- 稍微复杂一些

---

## 最佳实践建议

### 1. 字段数量决定方案

- **1-3 个字段**：使用 `metadata` 字段
- **4-8 个字段**：继承 User 模型添加字段
- **9+ 个字段**：使用关联表

### 2. 查询需求决定方案

- **很少查询**：使用 `metadata` 字段
- **经常查询和过滤**：添加独立字段
- **复杂关联查询**：使用关联表

### 3. 混合使用

可以同时使用多种方案：

```php
class User extends \WeiJuKeJi\LaravelIam\Models\User
{
    // 常用的核心字段直接添加到表
    protected $fillable = [
        ...parent::$fillable,
        'sales_channel',  // 经常查询的字段
        'merchant_id',    // 经常查询的字段
    ];

    // 不常用的配置放在 metadata 中
    // $user->metadata = ['preferences' => [...], 'settings' => [...]]

    // 复杂的扩展信息使用关联表
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
}
```

### 4. 数据库索引

无论使用哪种方案，记得为经常查询的字段添加索引：

```php
// 普通字段索引
$table->index('sales_channel');
$table->index('merchant_id');

// PostgreSQL 的 jsonb 索引
$table->index(DB::raw("(metadata->>'sales_channel')"));

// PostgreSQL GIN 索引（用于复杂查询）
DB::statement('CREATE INDEX users_metadata_gin ON users USING gin(metadata)');
```

---

## 示例：完整的实现

这里展示一个实际项目中的完整示例：

### 1. 自定义 User 模型

```php
<?php

namespace App\Models;

use WeiJuKeJi\LaravelIam\Models\User as BaseUser;

class User extends BaseUser
{
    protected $fillable = [
        ...parent::$fillable,
        'sales_channel',
        'merchant_id',
    ];

    protected $casts = [
        ...parent::$casts,
        'merchant_id' => 'integer',
    ];

    // 关联关系
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // 查询作用域
    public function scopeOnlineChannel($query)
    {
        return $query->where('sales_channel', 'online');
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    // 业务方法
    public function isOnlineChannel(): bool
    {
        return $this->sales_channel === 'online';
    }

    public function canAccessMerchant(int $merchantId): bool
    {
        return $this->merchant_id === $merchantId
            || $this->hasRole('super-admin');
    }
}
```

### 2. 配置绑定

`config/iam.php`:

```php
'models' => [
    'user' => \App\Models\User::class,
],

'user_metadata_schema' => [
    'preferences' => [
        'type' => 'array',
        'description' => '用户偏好设置',
    ],
    'notification_settings' => [
        'type' => 'array',
        'description' => '通知设置',
    ],
],
```

### 3. 使用示例

```php
// 创建用户
$user = User::create([
    'name' => '王五',
    'email' => 'wangwu@example.com',
    'username' => 'wangwu',
    'password' => 'password',
    'sales_channel' => 'online',
    'merchant_id' => 123,
    'metadata' => [
        'preferences' => [
            'theme' => 'dark',
            'language' => 'zh-CN',
        ],
    ],
]);

// 分配角色和权限（继承自 IAM）
$user->assignRole('sales-manager');

// 查询
$onlineUsers = User::onlineChannel()->get();
$merchantUsers = User::byMerchant(123)->with('merchant')->get();

// 权限检查（继承自 IAM + 自定义业务逻辑）
if ($user->canAccessMerchant(123) && $user->hasPermissionTo('orders.view')) {
    // 允许访问
}
```

---

## 常见问题

### Q1: 修改了配置中的模型绑定，为什么没有生效？

清除配置缓存：

```bash
php artisan config:clear
php artisan cache:clear
```

### Q2: 如何在已有用户上添加新字段？

创建一个迁移来更新现有数据：

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('sales_channel', 32)->default('offline')->after('status');
    });

    // 为现有用户设置默认值
    DB::table('users')->update(['sales_channel' => 'offline']);
}
```

### Q3: metadata 字段的查询性能如何优化？

**PostgreSQL**：创建 GIN 索引

```sql
CREATE INDEX users_metadata_gin ON users USING gin(metadata);
```

**MySQL**：创建虚拟列和索引

```sql
ALTER TABLE users
ADD COLUMN sales_channel_virtual VARCHAR(32)
AS (metadata->>'$.sales_channel') STORED;

CREATE INDEX idx_sales_channel ON users(sales_channel_virtual);
```

### Q4: 如何在 API 响应中隐藏某些字段？

在 User 模型中使用 `$hidden` 或 API Resource：

```php
// 方法 1：模型中定义
protected $hidden = [
    'password',
    'remember_token',
    'merchant_id', // 隐藏敏感字段
];

// 方法 2：使用 API Resource
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'sales_channel' => $this->when(
                $request->user()->can('view-sensitive-data'),
                $this->sales_channel
            ),
        ];
    }
}
```

---

## 总结

选择合适的方案取决于你的具体需求：

- **快速开发、字段少**：使用 `metadata` 字段
- **需要性能、类型安全**：继承 User 模型添加字段
- **字段很多、需要解耦**：使用关联表
- **实际项目**：混合使用以上方案

无论选择哪种方案，Laravel IAM 都为你提供了坚实的基础，让你可以专注于业务逻辑的实现。
