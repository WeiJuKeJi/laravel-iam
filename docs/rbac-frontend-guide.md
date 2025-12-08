# 前端实现指南：用户 / 角色 / 权限管理

本文面向负责实现 **用户、角色、权限** 管理功能的前端同学，帮助你在现有后台框架中快速落地增删改查界面。请严格按本文指引，与后端接口保持一致，避免任意猜测或随意新增字段。

---

## 1. 功能目标概览

1. **用户管理**：支持过滤查询、分页展示、创建 / 编辑 / 删除用户，绑定角色并展示用户直接权限。
2. **角色管理**：维护角色基本信息，支持绑定权限、查看角色下的权限列表，禁止删除系统预置角色（后端已校验）。
3. **权限管理**：列表展示所有权限，支持创建 / 更新 / 删除（用于权限维护与调试）。
4. **数据一致性**：所有操作成功后需刷新列表；必要时同步刷新其他模块（如刷新用户详情、角色列表等）。

---

## 2. 接口与数据结构

### 2.1 API 列表

| 模块 | 接口 | 方法 | 权限要求 | 说明 |
| --- | --- | --- | --- | --- |
| 用户 | `/api/v1/iam/users` | `GET` | `iam.users.view` | 分页查询用户，可按状态/关键字等筛选 |
|  | `/api/v1/iam/users` | `POST` | `iam.users.manage` | 创建用户 |
|  | `/api/v1/iam/users/{user}` | `GET` | `iam.users.view` | 获取用户详情，可附带 roles / permissions |
|  | `/api/v1/iam/users/{user}` | `PUT` | `iam.users.manage` | 更新用户（含角色同步） |
|  | `/api/v1/iam/users/{user}` | `DELETE` | `iam.users.manage` | 删除用户，后端会同时回收 Token |
| 角色 | `/api/v1/iam/roles` | `GET` | `iam.roles.view` | 分页查询角色，可加载权限 |
|  | `/api/v1/iam/roles` | `POST` | `iam.roles.manage` | 创建角色并绑定权限 |
|  | `/api/v1/iam/roles/{role}` | `GET` | `iam.roles.view` | 获取角色详情，可加载权限 |
|  | `/api/v1/iam/roles/{role}` | `PUT` | `iam.roles.manage` | 更新角色与权限绑定 |
|  | `/api/v1/iam/roles/{role}` | `DELETE` | `iam.roles.manage` | 删除角色，超级管理员角色禁止删除 |
| 权限 | `/api/v1/iam/permissions` | `GET` | `iam.permissions.view` | 分页查询权限（模块/关键字过滤） |
|  | `/api/v1/iam/permissions` | `POST` | `iam.permissions.manage` | 创建权限 |
|  | `/api/v1/iam/permissions/{permission}` | `GET` | `iam.permissions.view` | 获取权限详情 |
|  | `/api/v1/iam/permissions/{permission}` | `PUT` | `iam.permissions.manage` | 更新权限 |
|  | `/api/v1/iam/permissions/{permission}` | `DELETE` | `iam.permissions.manage` | 删除权限 |

### 2.2 请求头与认证

- 所有接口均需携带：
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
  - 写操作额外带 `Content-Type: application/json`
- Token 来自登录接口 `/api/v1/iam/auth/login`，当前用户信息可通过 `/api/v1/iam/auth/me` 获取。

### 2.3 通用返回结构

所有接口都遵循统一响应规范：

```json
{
  "code": 200,
  "msg": "success",
  "data": { ... }
}
```

- 列表返回：`data` 内含 `list`、`total`
- 详情返回：`data` 为对象
- 创建成功返回 `code=201` 且 HTTP 状态码 201；删除成功返回空对象
- 校验失败返回 `code=422`，`data.errors` 中包含字段错误详情

---

## 3. 用户管理实现要点

### 3.1 查询与过滤

- 支持以下查询参数：`status`、`keywords`、`email`、`username`、`role`、`per_page`
- `with_roles=true` 加载用户的角色及角色权限；`with_permissions=true` 加载用户直接绑定的权限
- 表格列建议包含：姓名/昵称、账号、邮箱、状态、角色列表、最后登录时间等
- 状态字段（`status`）后端默认 `active`，可下拉筛选

### 3.2 创建 / 编辑用户

**请求体示例**

```json
{
  "name": "运营管理员",
  "email": "ops01@settlehub.local",
  "username": "ops01",
  "password": "Ops@123456",
  "status": "active",
  "phone": "13800001111",
  "metadata": {
    "department": "运营中心"
  },
  "roles": [1, 2]
}
```

- 创建后端会自动填充 `guard_name`，无需传递
- 编辑时若密码留空后端会忽略更新，表单建议在「修改密码」场景单独输入
- 角色选择：调用 `/roles?per_page=1000` 获取全量角色，下拉多选即可
- 成功后刷新列表，并提示用户操作结果

### 3.3 删除用户

- 删除前须提醒「确认删除用户并回收 Token」
- 后端会同时删除 Sanctum Token，前端只需刷新列表
- 删除超级管理员不会被阻止，但需二次确认

---

## 4. 角色管理实现要点

### 4.1 列表与详情

- 支持 `keywords`、`guard_name`、`per_page` 等查询参数
- `with_permissions=true` 时后端返回 `permissions` 数组，可直接渲染标签列表或检查状态
- 表格列建议包含：角色名称、显示名称、描述分组、权限数量、创建/更新时间

### 4.2 创建 / 编辑角色

**请求体示例**

```json
{
  "name": "ops-manager",
  "display_name": "运营管理员",
  "group": "运营",        // 可选
  "metadata": {
    "description": "负责运营后台管理"
  },
  "permissions": ["iam.users.view", "iam.users.manage"]
}
```

- 权限选择建议展示可搜索的多选列表，可以按模块 / 资源分组（权限表里有 `group`、`display_name`）
- 更新角色时若 `permissions` 为空数组表示清空绑定；若不传则维持原绑定
- 删除角色时，后端会对系统预置角色（如 `super-admin`）返回 422，前端需针对错误提示友好输出

---

## 5. 权限管理实现要点

### 5.1 列表

- 支持 `keywords`、`guard_name`、`per_page` 查询字段
- 建议展示：权限名、显示名、所属组（模块.资源）、守卫名、创建时间等
- 权限较多时可提供搜索框 + 分页

### 5.2 创建 / 编辑

**请求体示例**

```json
{
  "name": "mdm.projects.view",
  "display_name": "mdm.项目.查看",
  "group": "mdm.项目",
  "guard_name": "sanctum"
}
```

- `guard_name` 默认 `sanctum`，如无特殊情况可以隐藏该字段只传默认值
- 编辑权限后不需要刷新角色界面，但若要即时更新角色的展示可以触发局部重载

### 5.3 删除

- 删除前确认是否被角色使用；若后端返回 500/400，需提示「权限可能被角色引用，请先解绑」

---

## 6. 交互与状态处理

1. **统一 loading / empty / error 状态**，所有表格与弹窗操作需有 Loading 指示
2. **通知提示**：增删改成功必须提示用户，失败需展示后端返回的 `msg` 或 `data.errors`
3. **权限控制**：
   - 「新建用户」按钮仅在用户拥有 `iam.users.manage` 权限时显示
   - 可通过后端返回的权限列表（登录后 `/auth/me` 返回）或现有 `v-permission` 指令控制
4. **缓存刷新**：
   - 用户新增/更新后刷新用户列表；
   - 角色更新后如有必要刷新用户详情，以显示最新角色信息；
   - 权限更新后如需要可提示管理员重新同步角色。

---

## 7. 测试建议

1. **接口联调**：在 Apifox 中验证所有接口请求示例，确认带 `Authorization`、`Accept` 头可正常响应。
2. **集成测试场景**：
   - 新建角色 → 绑定权限 → 为用户赋予该角色 → 登录检查菜单/功能是否生效
   - 删除角色（非系统角色） → 用户角色列表对应更新
   - 禁止性操作：删除 `super-admin`、创建重复权限名，确保前端错误提示清晰
3. **权限开关测试**：使用不同账号（如 `Admin`、`Editor`）登录，确认按钮、菜单显隐符合预期。

---

## 8. 落地 Checklist

- [ ] 登录后通过 `/auth/me` 获取并缓存用户角色/权限
- [ ] 用户列表支持过滤、分页、角色/权限展示
- [ ] 用户表单支持角色多选、密码修改、表单校验
- [ ] 角色列表支持权限可视化、角色表单支持多权限绑定
- [ ] 权限列表与编辑功能可正确维护 `name/display_name/group`
- [ ] 所有写操作均使用统一成功/失败提示，并刷新相关列表
- [ ] 按实际权限隐藏新增/编辑/删除按钮；未授权操作返回 403 时提示“无访问权限”

---

对接过程中如发现接口字段或交互需求不符，可及时与后端沟通确认，切勿自行修改接口约定。祝开发顺利！💪

