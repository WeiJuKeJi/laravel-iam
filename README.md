# Laravel IAM

Laravel Identity and Access Management (IAM) package - 用户、角色、权限、菜单、部门和登录日志管理。

## 功能

- **用户管理** - CRUD、角色分配、部门/商户关联、软删除
- **角色管理** - RBAC、权限绑定、菜单绑定、用户绑定
- **权限管理** - 细粒度权限控制，分组管理（基于 Spatie Permission）
- **菜单管理** - 嵌套树形结构、角色访问控制、公共菜单、外链/内嵌支持
- **部门管理** - 树形组织架构，部门层级拖拽排序
- **登录日志** - 登录记录查询，个人日志查看
- **权限同步** - 从路由自动生成权限
- **菜单导出** - 导出菜单配置为 JSON

## 依赖

- PHP >= 8.2
- Laravel >= 11.0（支持 Laravel 12）
- MySQL / PostgreSQL

## 安装

```bash
# 安装扩展包
composer require weijukeji/laravel-iam

# 运行安装命令
php artisan iam:install --seed
```

安装选项：
- `--seed` - 运行数据填充（默认角色、权限、管理员账号）
- `--force` - 覆盖已有配置文件
- `--no-migrate` - 跳过迁移
- `--sync-permissions` - 同步路由权限

### 手动安装

```bash
# 发布配置
php artisan vendor:publish --tag=iam-config

# 发布并运行迁移
php artisan vendor:publish --tag=iam-migrations
php artisan migrate

# 填充数据（可选）
php artisan db:seed --class="WeiJuKeJi\\LaravelIam\\Database\\Seeders\\IamDatabaseSeeder"
```

数据填充创建：
- 默认权限（用户/角色/权限/菜单管理）
- 默认角色（super-admin、Admin、Editor）
- 管理员账号：`admin@settlehub.local` / `Admin@123456`
- 默认菜单结构

## 配置

编辑 `config/iam.php`：

```php
return [
    // 数据表前缀
    'table_prefix' => 'iam_',

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

### 表前缀

`table_prefix` 影响以下表：

| 表名 | 说明 |
|---|---|
| `{prefix}permissions` | 权限表 |
| `{prefix}roles` | 角色表 |
| `{prefix}model_has_permissions` | 用户-权限关联 |
| `{prefix}model_has_roles` | 用户-角色关联 |
| `{prefix}role_has_permissions` | 角色-权限关联 |
| `{prefix}menus` | 菜单表 |
| `{prefix}menu_role` | 菜单-角色关联 |
| `{prefix}departments` | 部门表 |

建议在安装前配置前缀。已安装的项目修改前缀后需回滚并重新运行迁移。

### 缓存

建议使用支持标签的缓存驱动（Redis / Memcached）。file / database 驱动也能工作，会使用备用的缓存键追踪机制。

## API 路由

默认路由前缀：`/api/iam`（可通过配置 `route_prefix` 修改）

### 认证

| 方法 | 路径 | 说明 | 限制 |
|---|---|---|---|
| POST | `/auth/login` | 登录 | 5次/分钟 |
| POST | `/auth/logout` | 登出 | 需认证 |
| GET | `/auth/me` | 当前用户信息 | 需认证 |

### 菜单（权限：`iam.menus.view` / `iam.menus.manage`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/routes` | 当前用户的路由菜单（带缓存） |
| GET | `/menus/tree` | 菜单树（管理端） |
| GET | `/menus` | 菜单列表 |
| POST | `/menus` | 创建菜单 |
| GET | `/menus/{id}` | 菜单详情 |
| PUT | `/menus/{id}` | 更新菜单 |
| DELETE | `/menus/{id}` | 删除菜单 |

### 用户（权限：`iam.users.view` / `iam.users.manage`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/users` | 用户列表 |
| POST | `/users` | 创建用户 |
| GET | `/users/{id}` | 用户详情 |
| PUT | `/users/{id}` | 更新用户 |
| DELETE | `/users/{id}` | 删除用户 |

可通过配置 `disabled_routes` 禁用用户路由。

### 角色（权限：`iam.roles.view` / `iam.roles.manage`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/roles` | 角色列表（支持 `with_permissions`、`with_menus`、`with_users_count`） |
| POST | `/roles` | 创建角色 |
| GET | `/roles/{id}` | 角色详情（支持 `with_permissions`、`with_menus`） |
| PUT | `/roles/{id}` | 更新角色 |
| DELETE | `/roles/{id}` | 删除角色（super-admin 禁止删除） |

角色创建/更新支持字段：`name`、`display_name`、`group`、`metadata`、`permissions`（权限 ID 数组）、`menu_ids`（菜单 ID 数组）。

### 权限（权限：`iam.permissions.view` / `iam.permissions.manage`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/permissions` | 权限列表 |
| GET | `/permissions/groups` | 权限分组树 |
| POST | `/permissions` | 创建权限 |
| GET | `/permissions/{id}` | 权限详情 |
| PUT | `/permissions/{id}` | 更新权限 |
| DELETE | `/permissions/{id}` | 删除权限 |

### 部门（权限：`iam.departments.view` / `iam.departments.manage`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/departments` | 部门列表 |
| GET | `/departments/tree` | 部门树 |
| POST | `/departments` | 创建部门 |
| GET | `/departments/{id}` | 部门详情 |
| PUT | `/departments/{id}` | 更新部门 |
| DELETE | `/departments/{id}` | 删除部门 |
| POST | `/departments/{id}/move` | 移动部门 |

### 登录日志（权限：`iam.login-logs.view`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/login-logs` | 登录日志列表 |
| GET | `/login-logs/{id}` | 日志详情 |
| GET | `/login-logs/my` | 当前用户的登录日志 |

## Artisan 命令

```bash
# 安装
php artisan iam:install [--seed] [--force] [--no-migrate] [--sync-permissions]

# 同步权限（从路由自动生成）
php artisan iam:sync-permissions

# 导出菜单为 JSON
php artisan iam:menus:export [path]

# 重置菜单数据（清空并重新填充）
php artisan iam:menu:reseed [--force]

# 卸载
php artisan iam:uninstall [--force] [--keep-tables]
```

## 菜单访问控制

菜单可见性判断逻辑：

1. **未启用** → 不可见
2. **super-admin 角色** → 全部可见
3. **公共菜单**（`is_public = true`）→ 所有登录用户可见
4. **关联了角色** → 用户必须拥有其中一个角色才可见
5. **未关联任何角色且非公共** → 不可见

菜单与角色的绑定可从两个方向管理：
- **角色编辑** → "绑定菜单" Tab（选择角色能看到的菜单）
- **菜单编辑** → "绑定角色" Tab（选择哪些角色能看到此菜单）

菜单树按用户角色缓存，缓存 TTL 30 分钟，修改菜单或菜单-角色关联时自动刷新。

## 模型

### User

继承 `Illuminate\Foundation\Auth\User`，使用 `HasApiTokens`、`HasRoles`、`SoftDeletes`、`Filterable`。

### Role

扩展 Spatie Permission Role，额外字段：`display_name`、`group`、`metadata`。

关系：`permissions`（多对多）、`menus`（多对多，通过 `menu_role`）、`users`（多态多对多）。

### Permission

扩展 Spatie Permission，额外字段：`display_name`、`group`、`metadata`。

### Menu

字段：`parent_id`、`name`、`path`、`component`、`redirect`、`sort_order`、`is_enabled`、`is_public`、`meta`。

关系：`parent`、`children`、`roles`（多对多，通过 `menu_role`）。

支持外链（`path` 以 `//` 开头）和内嵌网页（通过 iframe 组件）。

### Department

树形组织架构，支持层级排序和拖拽移动。

## 代码示例

```php
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Services\MenuService;

$user = User::find(1);

// 权限检查
$user->hasPermissionTo('iam.users.view');
$user->hasRole('super-admin');

// 角色和权限分配
$user->assignRole('admin');
$user->givePermissionTo('iam.users.manage');

// 菜单服务
$menuService = app(MenuService::class);
$menus = $menuService->getMenuTreeForUser($user);
$menus = $menuService->getMenuTreeForUser($user, forceRefresh: true);
$menuService->flushCache();
```

## 卸载

```bash
# 1. 运行卸载命令
php artisan iam:uninstall

# 2. 移除包（必须加 --no-scripts）
composer remove weijukeji/laravel-iam --no-scripts

# 3. 重建包发现缓存
php artisan package:discover --ansi
```

一行命令：

```bash
php artisan iam:uninstall --force && composer remove weijukeji/laravel-iam --no-scripts && php artisan package:discover --ansi
```

## 前端集成

- [前端路由指南](docs/menu-routing.md)
- [RBAC 前端集成](docs/rbac-frontend-guide.md)
- [菜单前端指南](docs/menu-frontend-guide.md)
- [用户模型扩展指南](docs/user-extension-guide.md)
- [部门管理指南](docs/department-guide.md)
- [登录日志指南](docs/login-log-guide.md)
- [权限分组树](docs/permission-group-tree.md)

## 许可证

MIT License. 详见 [LICENSE](LICENSE)。
