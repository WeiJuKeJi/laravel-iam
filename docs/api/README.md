# Iam 模块 API 文档索引

本目录包含 Iam（身份认证与访问管理）模块的所有 API 接口文档（OpenAPI 3.0.3 格式）。

## 如何使用

1. 使用 Apifox 导入对应的 JSON 文件
2. 根据下表快速定位控制器代码位置
3. 参考控制器方法表了解接口实现细节

---

## API 文档列表

| API 文档 | 对应控制器 | 说明 |
|---------|-----------|------|
| [认证管理.json](./认证管理.json) | AuthController | 用户登录、登出、刷新令牌等认证相关接口 |
| [用户管理.json](./用户管理.json) | UserController | 用户的增删改查及角色分配 |
| [角色管理.json](./角色管理.json) | RoleController | 角色的增删改查及权限分配 |
| [权限管理.json](./权限管理.json) | PermissionController | 权限的查询和管理 |
| [菜单管理.json](./菜单管理.json) | MenuAdminController | 后台菜单的增删改查 |
| [前端路由.json](./前端路由.json) | MenuController | 前端路由配置获取 |

---

## 控制器详情

### AuthController

**文件路径**: `app/Http/Controllers/AuthController.php`
**API 文档**: [认证管理.json](./认证管理.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| login() | POST | /iam/auth/login | 用户登录 | login |
| logout() | POST | /iam/auth/logout | 用户登出 | logout |
| refresh() | POST | /iam/auth/refresh | 刷新令牌 | refreshToken |
| me() | GET | /iam/auth/me | 获取当前用户信息 | getCurrentUser |

### UserController

**文件路径**: `app/Http/Controllers/UserController.php`
**API 文档**: [用户管理.json](./用户管理.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| index() | GET | /iam/users | 获取用户列表 | getUsers |
| store() | POST | /iam/users | 创建用户 | createUser |
| show() | GET | /iam/users/{id} | 获取用户详情 | getUser |
| update() | PUT | /iam/users/{id} | 更新用户 | updateUser |
| destroy() | DELETE | /iam/users/{id} | 删除用户 | deleteUser |

### RoleController

**文件路径**: `app/Http/Controllers/RoleController.php`
**API 文档**: [角色管理.json](./角色管理.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| index() | GET | /iam/roles | 获取角色列表 | getRoles |
| store() | POST | /iam/roles | 创建角色 | createRole |
| show() | GET | /iam/roles/{id} | 获取角色详情 | getRole |
| update() | PUT | /iam/roles/{id} | 更新角色 | updateRole |
| destroy() | DELETE | /iam/roles/{id} | 删除角色 | deleteRole |

### PermissionController

**文件路径**: `app/Http/Controllers/PermissionController.php`
**API 文档**: [权限管理.json](./权限管理.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| index() | GET | /iam/permissions | 获取权限列表 | getPermissions |
| store() | POST | /iam/permissions | 创建权限 | createPermission |
| show() | GET | /iam/permissions/{id} | 获取权限详情 | getPermission |
| update() | PUT | /iam/permissions/{id} | 更新权限 | updatePermission |
| destroy() | DELETE | /iam/permissions/{id} | 删除权限 | deletePermission |

### MenuAdminController

**文件路径**: `app/Http/Controllers/MenuAdminController.php`
**API 文档**: [菜单管理.json](./菜单管理.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| index() | GET | /iam/menus | 获取菜单列表 | getMenus |
| tree() | GET | /iam/menus/tree | 获取菜单树 | getMenuTree |
| store() | POST | /iam/menus | 创建菜单 | createMenu |
| show() | GET | /iam/menus/{id} | 获取菜单详情 | getMenu |
| update() | PUT | /iam/menus/{id} | 更新菜单 | updateMenu |
| destroy() | DELETE | /iam/menus/{id} | 删除菜单 | deleteMenu |

### MenuController

**文件路径**: `app/Http/Controllers/MenuController.php`
**API 文档**: [前端路由.json](./前端路由.json)

| 控制器方法 | HTTP 方法 | 路由 | 接口说明 | operationId |
|-----------|----------|------|---------|-------------|
| index() | GET | /iam/routes | 获取前端路由配置 | getFrontendRoutes |

---

## 版本历史

| 版本 | 日期 | 说明 |
|-----|------|------|
| 1.0.0 | 2025-11-21 | 初始版本 |
| 1.1.0 | 2025-11-25 | 按新规范优化，添加 operationId 列 |
