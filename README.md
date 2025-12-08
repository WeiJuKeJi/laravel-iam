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

## 依赖要求

- PHP >= 8.2
- Laravel >= 11.0 或 12.0
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

    // 路由前缀
    'route_prefixes' => ['iam'],

    // 忽略的路由（不需要权限验证）
    'ignore_routes' => [
        'iam.auth.login',
        'iam.auth.logout',
        'iam.auth.me',
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
        'Admin',
    ],
];
```

## 使用

### API 路由

包默认注册以下 API 路由（前缀：`/v1/iam`）：

#### 认证相关
- `POST /v1/iam/auth/login` - 用户登录
- `POST /v1/iam/auth/logout` - 用户登出
- `GET /v1/iam/auth/me` - 获取当前用户信息

#### 菜单管理
- `GET /v1/iam/routes` - 获取当前用户的路由菜单
- `GET /v1/iam/menus/tree` - 获取菜单树
- `GET /v1/iam/menus` - 菜单列表
- `POST /v1/iam/menus` - 创建菜单
- `GET /v1/iam/menus/{id}` - 查看菜单详情
- `PUT /v1/iam/menus/{id}` - 更新菜单
- `DELETE /v1/iam/menus/{id}` - 删除菜单

#### 用户管理
- `GET /v1/iam/users` - 用户列表
- `POST /v1/iam/users` - 创建用户
- `GET /v1/iam/users/{id}` - 查看用户
- `PUT /v1/iam/users/{id}` - 更新用户
- `DELETE /v1/iam/users/{id}` - 删除用户

#### 角色管理
- `GET /v1/iam/roles` - 角色列表
- `POST /v1/iam/roles` - 创建角色
- `GET /v1/iam/roles/{id}` - 查看角色
- `PUT /v1/iam/roles/{id}` - 更新角色
- `DELETE /v1/iam/roles/{id}` - 删除角色

#### 权限管理
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
php artisan iam:export-menus
```

### 在代码中使用

#### 检查权限

```php
use WeiJuKeJi\LaravelIam\Models\User;

$user = User::find(1);

// 检查是否有特定权限
if ($user->hasPermissionTo('users.view')) {
    // 用户有查看用户的权限
}

// 检查是否有特定角色
if ($user->hasRole('admin')) {
    // 用户是管理员
}
```

#### 分配角色和权限

```php
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Models\Role;

$user = User::find(1);

// 分配角色
$user->assignRole('admin');

// 移除角色
$user->removeRole('admin');

// 直接分配权限
$user->givePermissionTo('users.manage');

// 移除权限
$user->revokePermissionTo('users.manage');
```

#### 使用菜单服务

```php
use WeiJuKeJi\LaravelIam\Services\MenuService;

$menuService = app(MenuService::class);

// 获取用户的菜单树
$menus = $menuService->getUserMenuTree($user);

// 获取路由映射
$routes = $menuService->getRouteMapping();
```

## 模型说明

### User

用户模型，继承自 `Illuminate\Foundation\Auth\User`，使用了：
- `HasApiTokens` - Sanctum API 认证
- `HasRoles` - Spatie Permission 角色功能
- `SoftDeletes` - 软删除
- `Filterable` - 高级筛选

### Role

角色模型，来自 Spatie Permission 包，支持：
- 角色分配
- 权限管理
- 角色继承

### Permission

权限模型，来自 Spatie Permission 包，支持：
- 细粒度权限控制
- 权限分组

### Menu

菜单模型，使用 Nested Set 实现树形结构，支持：
- 无限层级嵌套
- 父子关系管理
- 路由映射
- 权限关联

## 前端集成

详细的前端集成指南请查看：
- [前端路由指南](docs/menu-routing.md)
- [RBAC 前端集成](docs/rbac-frontend-guide.md)
- [菜单前端指南](docs/menu-frontend-guide.md)

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
