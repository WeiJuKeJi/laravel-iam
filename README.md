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
- Laravel >= 11.0 (支持 Laravel 12)
- MySQL / PostgreSQL

## 安装

### 快速安装（推荐）

```bash
# 1. 安装扩展包
composer require weijukeji/laravel-iam

# 2. 运行安装命令（发布配置、迁移、填充数据）
php artisan iam:install --seed
```

安装命令支持以下选项：
- `--seed` - 运行数据库填充（创建默认角色、权限、管理员账号）
- `--force` - 覆盖已存在的配置文件
- `--no-migrate` - 跳过数据库迁移
- `--sync-permissions` - 同步路由权限

### 手动安装

如果需要更精细的控制，可以手动执行各个步骤：

#### 1. 通过 Composer 安装

```bash
composer require weijukeji/laravel-iam
```

#### 2. 发布配置文件

```bash
php artisan vendor:publish --tag=iam-config
```

这将发布 `config/iam.php` 配置文件。

#### 3. 发布并运行迁移

```bash
php artisan vendor:publish --tag=iam-migrations
php artisan migrate
```

#### 4. （可选）运行数据填充

```bash
php artisan db:seed --class="WeiJuKeJi\\LaravelIam\\Database\\Seeders\\IamDatabaseSeeder"
```

这将创建：
- 默认权限（用户/角色/权限/菜单管理）
- 默认角色（super-admin、Admin、Editor）
- 管理员账号：`admin@settlehub.local` / `Admin@123456`
- 默认菜单结构

#### 5. （可选）发布视图

```bash
php artisan vendor:publish --tag=iam-views
```

## 配置

编辑 `config/iam.php` 文件来自定义配置：

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

### 配置说明

#### 数据表前缀 (`table_prefix`)

定义 IAM 扩展包所有数据表的前缀，默认为 `iam_`。

**影响的表**：
- `{prefix}permissions` - 权限表
- `{prefix}roles` - 角色表
- `{prefix}model_has_permissions` - 用户权限关联表
- `{prefix}model_has_roles` - 用户角色关联表
- `{prefix}role_has_permissions` - 角色权限关联表
- `{prefix}menus` - 菜单表
- `{prefix}menu_role` - 菜单角色关联表
- `{prefix}menu_permission` - 菜单权限关联表

**使用场景**：
- 避免与其他扩展包的表名冲突
- 符合项目的数据库命名规范
- 多租户应用中的表隔离

**修改方法**：
```php
'table_prefix' => 'app_',  // 表名将变为 app_permissions, app_roles 等
```

**⚠️ 重要提示**：
- **建议在安装前配置前缀**：在运行 `php artisan iam:install` 之前，先修改 `config/iam.php` 中的 `table_prefix`
- **迁移文件已支持动态前缀**：所有迁移文件都会读取配置文件中的前缀，自动创建对应的表名
- **已安装的项目**：如果已经运行过迁移，修改前缀后需要回滚并重新运行迁移，或手动调整数据库表名

**安装流程示例**（使用自定义前缀）：
```bash
# 1. 安装扩展包
composer require weijukeji/laravel-iam

# 2. 发布配置文件
php artisan vendor:publish --tag=iam-config

# 3. 修改配置文件中的 table_prefix
# 编辑 config/iam.php，将 'table_prefix' => 'iam_' 改为你想要的前缀

# 4. 运行安装命令
php artisan iam:install --seed
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

#### 安装扩展包

一键安装并初始化 Laravel IAM：

```bash
php artisan iam:install [--seed] [--force] [--no-migrate] [--sync-permissions]
```

选项说明：
- `--seed`：运行数据库填充
- `--force`：覆盖已存在的配置文件
- `--no-migrate`：跳过数据库迁移
- `--sync-permissions`：同步路由权限

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

#### 重置菜单数据

清空现有菜单数据并重新填充：

```bash
php artisan iam:menu:reseed [--force]
```

选项说明：
- `--force`：跳过确认提示直接执行

该命令会：
- 清空所有菜单数据（包括菜单表和关联表）
- 重新运行 MenuSeeder 填充默认菜单
- 自动清理菜单缓存

> **注意**：此操作会删除所有现有菜单数据，请谨慎使用。通常在开发环境更新菜单结构时使用。

#### 卸载扩展包

安全卸载 Laravel IAM（详见 [卸载](#卸载) 章节）：

```bash
php artisan iam:uninstall [--force] [--keep-tables]
```

选项说明：
- `--force`：跳过确认提示直接执行
- `--keep-tables`：不显示数据库表清理提示

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
- **外链支持** - 跳转到外部网站
- **内嵌网页** - 在系统内嵌入第三方页面

#### 外链配置

```json
{
  "name": "ExternalLink",
  "path": "//github.com/your-repo",
  "meta": {
    "title": "GitHub",
    "icon": "external-link-line",
    "target": "_blank"
  }
}
```

关键字段：
- `path`: 以 `//` 开头的外部链接
- `meta.target`: 设为 `_blank` 在新标签页打开

#### 内嵌网页配置

```json
{
  "name": "Iframe",
  "path": "/iframe",
  "component": "Layout",
  "meta": {
    "title": "内嵌网页",
    "icon": "window-line"
  },
  "children": [
    {
      "name": "IframeView",
      "path": "view",
      "component": "/@/views/other/iframe/view.vue",
      "meta": {
        "title": "Iframe",
        "dynamicNewTab": true,
        "hidden": true
      }
    },
    {
      "name": "DocSite",
      "path": "view?url=example.com&title=文档站点",
      "meta": {
        "title": "文档站点"
      }
    }
  ]
}
```

关键字段：
- `meta.dynamicNewTab`: 动态标签页
- `meta.hidden`: 隐藏菜单项
- `path`: 使用 `view?url=xxx&title=xxx` 格式传递参数

## 前端集成

详细的前端集成指南请查看：
- [前端路由指南](docs/menu-routing.md)
- [RBAC 前端集成](docs/rbac-frontend-guide.md)
- [菜单前端指南](docs/menu-frontend-guide.md)

## 用户模型扩展

如果你的项目需要为用户添加自定义字段（如销售渠道、商户 ID 等），请查看：
- [用户模型扩展指南](docs/user-extension-guide.md)

该指南介绍了三种扩展方式：
1. **使用 metadata 字段** - 适合简单场景，无需修改表结构
2. **继承 User 模型** - 适合需要类型安全和数据库索引的场景
3. **创建关联表** - 适合大量扩展字段的场景

## 安全特性

- **登录速率限制**：每分钟最多 5 次登录尝试
- **权限中间件**：所有管理接口都需要相应权限
- **软删除**：用户数据支持软删除，防止误删
- **密码加密**：使用 Laravel 原生 Hash 加密

## 卸载

由于 Laravel 的包自动发现机制，直接运行 `composer remove` 可能会因为缓存的 Service Provider 引用导致错误。请按以下步骤安全卸载：

### 1. 运行卸载命令

```bash
php artisan iam:uninstall
```

此命令会：
- 清理应用缓存（config、route、view）
- 清理 bootstrap 缓存（packages.php、services.php）
- 清理 IAM 菜单缓存
- 显示需要手动处理的数据库表

### 2. 移除 Composer 包

```bash
composer remove weijukeji/laravel-iam --no-scripts
```

> **重要**：必须添加 `--no-scripts` 参数，否则 Laravel 的 `pre-package-uninstall` 钩子会因为找不到已删除的类而报错。

### 3. 重建包发现缓存

```bash
php artisan package:discover --ansi
```

### 4. （可选）清理数据库

如果需要删除 IAM 相关的数据库表，可以手动运行：

```bash
php artisan migrate:rollback --path=vendor/weijukeji/laravel-iam/database/migrations
```

或者在运行卸载命令前保存迁移文件，然后手动执行回滚。

### 快速卸载（一行命令）

如果确定要卸载且不需要保留数据：

```bash
php artisan iam:uninstall --force && composer remove weijukeji/laravel-iam --no-scripts && php artisan package:discover --ansi
```

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
