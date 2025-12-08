# Laravel IAM

Laravel Identity and Access Management (IAM) package - 一个完整的用户、角色、权限和菜单管理解决方案。

## 功能特性

- 🔐 **用户管理** - 完整的用户 CRUD 操作和认证
- 👥 **角色管理** - 基于角色的访问控制 (RBAC)
- 🔑 **权限管理** - 细粒度的权限控制（基于 Spatie Permission）
- 📱 **菜单管理** - 支持嵌套树形结构的动态菜单系统
- 🔄 **权限同步** - 自动同步路由权限
- 📤 **菜单导出** - 导出菜单配置为 JSON 格式
- 🔍 **高级筛选** - 基于 EloquentFilter 的模型筛选
- 🛡️ **安全防护** - 登录速率限制、权限中间件保护

## 依赖要求

- PHP >= 8.2
- Laravel >= 11.0
- MySQL / PostgreSQL

## 安装

### 1. 通过 Composer 安装

```bash
composer require weijukeji/laravel-iam
```

### 2. 发布配置文件

```bash
php artisan vendor:publish --tag=iam-config
```

这将发布 `config/iam.php` 配置文件。

### 3. 发布并运行迁移

```bash
php artisan vendor:publish --tag=iam-migrations
php artisan migrate
```

### 4. （可选）发布 Seeders

如果需要初始数据，可以发布 seeders：

```bash
php artisan vendor:publish --tag=iam-seeders
php artisan db:seed --class=IamDatabaseSeeder
```

### 5. （可选）发布视图

如果需要自定义视图：

```bash
php artisan vendor:publish --tag=iam-views
```

## 配置

编辑 `config/iam.php` 文件来自定义配置：

```php
return [
    // 认证守卫
    'guard' => 'sanctum',

    // 路由前缀（用于权限同步）
    'route_prefixes' => ['iam'],

    // 忽略的路由（不需要权限验证）
    'ignore_routes' => [
        'iam.auth.login',
        'iam.auth.logout',
        'iam.auth.me',
        'iam.routes.index',
    ],

    // 动作映射
    'action_map' => [
        'index' => 'view',
        'show' => 'view',
        'store' => 'manage',
        'update' => 'manage',
        'destroy' => 'manage',
    ],

    // 需要同步的角色
    'sync_roles' => [
        'super-admin',
    ],
];
```

## 缓存配置

为获得最佳性能，建议使用支持标签的缓存驱动（如 Redis 或 Memcached）：

```env
CACHE_DRIVER=redis
```

如果使用 file 或 database 缓存驱动，菜单缓存仍能正常工作，但会使用备用的缓存键追踪机制。

## 使用

### API 路由

包默认注册以下 API 路由（前缀：`/v1/iam`）：

#### 认证相关
- `POST /v1/iam/auth/login` - 用户登录（带速率限制：5次/分钟）
- `POST /v1/iam/auth/logout` - 用户登出
- `GET /v1/iam/auth/me` - 获取当前用户信息

#### 菜单管理（需要权限：iam.menus.view / iam.menus.manage）
- `GET /v1/iam/routes` - 获取当前用户的路由菜单
- `GET /v1/iam/menus/tree` - 获取菜单树
- `GET /v1/iam/menus` - 菜单列表
- `POST /v1/iam/menus` - 创建菜单
- `GET /v1/iam/menus/{id}` - 查看菜单详情
- `PUT /v1/iam/menus/{id}` - 更新菜单
- `DELETE /v1/iam/menus/{id}` - 删除菜单

#### 用户管理（需要权限：iam.users.view / iam.users.manage）
- `GET /v1/iam/users` - 用户列表
- `POST /v1/iam/users` - 创建用户
- `GET /v1/iam/users/{id}` - 查看用户
- `PUT /v1/iam/users/{id}` - 更新用户
- `DELETE /v1/iam/users/{id}` - 删除用户

#### 角色管理（需要权限：iam.roles.view / iam.roles.manage）
- `GET /v1/iam/roles` - 角色列表
- `POST /v1/iam/roles` - 创建角色
- `GET /v1/iam/roles/{id}` - 查看角色
- `PUT /v1/iam/roles/{id}` - 更新角色
- `DELETE /v1/iam/roles/{id}` - 删除角色

#### 权限管理（需要权限：iam.permissions.view / iam.permissions.manage）
- `GET /v1/iam/permissions` - 权限列表
- `POST /v1/iam/permissions` - 创建权限
- `GET /v1/iam/permissions/{id}` - 查看权限
- `PUT /v1/iam/permissions/{id}` - 更新权限
- `DELETE /v1/iam/permissions/{id}` - 删除权限

### Artisan 命令

#### 同步权限

自动从路由生成权限：

```bash
php artisan iam:sync-permissions
```

#### 导出菜单

导出菜单配置为 JSON 文件：

```bash
php artisan iam:menus:export [path]
```

### 在代码中使用

#### 检查权限

```php
use WeiJuKeJi\LaravelIam\Models\User;

$user = User::find(1);

// 检查是否有特定权限
if ($user->hasPermissionTo('iam.users.view')) {
    // 用户有查看用户的权限
}

// 检查是否有特定角色
if ($user->hasRole('super-admin')) {
    // 用户是超级管理员
}
```

#### 分配角色和权限

```php
use WeiJuKeJi\LaravelIam\Models\User;

$user = User::find(1);

// 分配角色
$user->assignRole('admin');

// 移除角色
$user->removeRole('admin');

// 直接分配权限
$user->givePermissionTo('iam.users.manage');

// 移除权限
$user->revokePermissionTo('iam.users.manage');
```

#### 使用菜单服务

```php
use WeiJuKeJi\LaravelIam\Services\MenuService;

$menuService = app(MenuService::class);

// 获取用户的菜单树
$menus = $menuService->getMenuTreeForUser($user);

// 强制刷新缓存
$menus = $menuService->getMenuTreeForUser($user, forceRefresh: true);

// 清除所有菜单缓存
$menuService->flushCache();
```

## 模型说明

### User

用户模型，继承自 `Illuminate\Foundation\Auth\User`，使用了：
- `HasApiTokens` - Sanctum API 认证
- `HasRoles` - Spatie Permission 角色功能
- `SoftDeletes` - 软删除
- `Filterable` - 高级筛选

### Role

角色模型，扩展自 Spatie Permission，额外支持：
- `display_name` - 显示名称
- `group` - 角色分组
- `metadata` - 扩展字段

### Permission

权限模型，扩展自 Spatie Permission，额外支持：
- `display_name` - 显示名称
- `group` - 权限分组
- `metadata` - 扩展字段

### Menu

菜单模型，支持：
- 无限层级嵌套（树形结构）
- 父子关系管理
- 路由映射
- 角色和权限关联
- 自动缓存管理

## 前端集成

详细的前端集成指南请查看：
- [前端路由指南](docs/menu-routing.md)
- [RBAC 前端集成](docs/rbac-frontend-guide.md)
- [菜单前端指南](docs/menu-frontend-guide.md)

## 安全特性

- **登录速率限制**：每分钟最多 5 次登录尝试
- **权限中间件**：所有管理接口都需要相应权限
- **软删除**：用户数据支持软删除，防止误删
- **密码加密**：使用 Laravel 原生 Hash 加密

## 测试

```bash
composer test
```

## 许可证

MIT License. 详见 [LICENSE](LICENSE) 文件。

## 贡献

欢迎提交 Issue 和 Pull Request！

## 支持

如有问题，请提交 Issue 或联系：dev@weijukeji.com
