# 前端实现指南：动态菜单与后台管理

本文面向负责实现 **菜单路由加载** 与 **菜单管理后台** 的前端同学，提供从数据交互到界面落地的完整指引。请严格遵循本指南，以便与后端能力无缝衔接。

---

## 1. 项目背景与目标

1. **动态路由装载**：前端在用户登录后需要向后端拉取 `/api/v1/iam/routes` 返回的菜单树，根据用户角色/权限动态生成导航、侧边菜单及路由配置。
2. **菜单后台管理**：提供“菜单管理”页面，支持查看树形菜单、增删改节点、配置角色/权限绑定，并在操作完成后即时刷新前端缓存。
3. **缓存与版本**：利用响应头 `X-Menu-Version` 与返回数据中的 `version` 字段，实现菜单的增量刷新与缓存命中控制。

---

## 2. 接口与数据结构

### 2.1 API 列表

| 接口 | 方法 | 权限要求 | 说明 |
| --- | --- | --- | --- |
| `/api/v1/iam/routes` | `GET` | 登录态（`auth:sanctum`） | 获取当前用户可访问的菜单路由树，输出 `{list,total,version}`。 |
| `/api/v1/iam/menus` | `GET` | `iam.menus.view` | 获取后台管理使用的完整菜单树（含角色/权限绑定）。 |
| `/api/v1/iam/menus` | `POST` | `iam.menus.manage` | 新建菜单节点。 |
| `/api/v1/iam/menus/{menu}` | `GET` | `iam.menus.view` | 查询指定节点详情，用于编辑前填充。 |
| `/api/v1/iam/menus/{menu}` | `PUT` | `iam.menus.manage` | 更新节点及其绑定。 |
| `/api/v1/iam/menus/{menu}` | `DELETE` | `iam.menus.manage` | 删除节点（须无子节点）。 |

### 2.2 请求头规范

- 所有接口均需携带：
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
  - 写操作额外包含 `Content-Type: application/json`

### 2.3 数据模型

#### 2.3.1 `/routes` 接口返回

```ts
interface MenuRouteNode {
  path: string
  name: string
  component: string | null
  redirect?: string | null
  meta?: Record<string, any>
  guard?: string[] | { role: string[]; mode: 'include' | 'except' }
  children?: MenuRouteNode[]
}

interface RouteResponse {
  code: number
  msg: string
  data: {
    list: MenuRouteNode[]
    total: number
    version: string
  }
}
```

#### 2.3.2 `/menus` 接口返回

```ts
interface MenuTreeNode extends MenuRouteNode {
  id: number
  parent_id: number | null
  sort_order: number
  is_enabled: boolean
  roles: string[]
  permissions: string[]
  children?: MenuTreeNode[]
}

interface MenuListResponse {
  code: number
  msg: string
  data: {
    list: MenuTreeNode[]
    total: number
  }
}
```

#### 2.3.3 创建/更新请求体

```ts
interface MenuInput {
  parent_id: number | null
  name: string
  path: string
  component?: string | null
  redirect?: string | null
  sort_order?: number
  is_enabled?: boolean
  meta?: Record<string, any>
  guard?: string[] | { role: string[]; mode: 'include' | 'except' }
  role_ids?: number[]
  permission_ids?: number[]
}
```

---

## 3. 动态路由加载方案

### 3.1 流程总览

1. **登录成功**：后端返回 token 后，前端保存至状态管理/本地存储。
2. **拉取路由**：调用 `/routes` 接口获取菜单：
   - 缓存策略：读取本地保存的 `version`；若存在并与接口返回不同，则更新缓存并刷新路由。
   - 建议将 `{list, version}` 序列化后存储在 `localStorage` / `indexedDB`。
   - 若需要强制刷新（例如用户调整菜单后后台立刻取到最新数据），可调用 `/routes?refresh=1`，后台会跳过缓存返回最新结果。
3. **解析成路由配置**：
   - 遍历 `list`，将 `component` 从字符串转换为实际组件（可采用 `defineAsyncComponent` 或已约定的动态 import 映射表）。
   - 根据 `meta.guard`、`meta.role` 等字段设置 `meta.roles`、`meta.hidden`、`meta.icon` 等属性，供权限指令/导航菜单使用。
4. **挂载路由**：将解析后的路由通过 `router.addRoute` 动态注册。根节点通常挂载在 `Layout` 下。
5. **生成 UI 菜单**：将 `list` 直接作为侧边栏的数据源，渲染标题、图标、徽标、外链等属性。
6. **守卫处理**：
   - 登录路由守卫（`router.beforeEach`）中，若无菜单缓存则执行第 2 步；
   - 检查 `meta.guard` 与当前用户角色/权限（登录接口已返回 `roles`、`permissions`），不满足时跳转 403 页面。

### 3.2 缓存与刷新策略

- **初始加载**：优先使用缓存中 `list` 渲染菜单，随后异步比对 `version`，若不一致再刷新。
- **手动刷新**：菜单管理操作完成后，可触发 `dispatch('menu/fetchRoutes', { force: true })` 或直接请求 `/routes?refresh=1` 强制重拉。
- **异常回退**：若接口报错，清空缓存并提示用户重新登录或联系管理员。

### 3.3 推荐技术栈/结构

- 状态管理：使用 Pinia 或 Vuex 保存 `menus`、`routes`、`version`。
- 路由：Vue Router 动态加载；对 `component` 为 `Layout`、`Iframe` 等别名的节点单独处理。
- 组件：提供 `<SidebarMenu>` 组件接收菜单树，递归渲染。
- 权限指令：根据 `meta.guard` 与用户角色判断菜单显示与否。

---

## 4. 菜单管理后台实现指南

### 4.1 页面功能需求

1. **树形展示**：使用 Tree/Table 组件展示菜单层级，列出名称、路径、排序、启用状态、绑定角色/权限等信息。
2. **搜索/过滤**：支持按名称、角色、启用状态过滤。
3. **增/改操作**：
   - 侧边抽屉或弹窗表单，包含上述 `MenuInput` 字段；
   - `meta`/`guard` 字段可使用 JSON 编辑器或表单拆分（例如 title/icon/cache 等字段分别输入，再组装成对象）。
   - 角色、权限字段提供多选框，从 `/v1/iam/roles`、`/v1/iam/permissions` 获取列表。
4. **删除操作**：
   - 先检查 `children.length` 是否大于 0，前端应给出“仅可删除叶子节点”的提示；
   - 调用 DELETE 接口后刷新菜单树与 `/routes` 缓存。
5. **拖拽排序（可选）**：若需要拖动排序，务必在拖动结束后提交新的 `parent_id` 与 `sort_order`。后端暂未提供批量排序接口，可在前端一次性调用 PUT。

### 4.2 交互细节

- **表单校验**：必填字段如 `name`、`path`、`component`（非布局节点）必须校验；`sort_order` 使用数字输入，提示优先级越低数值越大。
- **状态提示**：接口成功后使用统一的消息提示（Success/Error），同时刷新列表。
- **并发更新**：编辑弹窗打开时建议拷贝数据（防止直接修改树节点导致 UI 跳变）。
- **权限显示**：根据当前用户权限，决定是否展示“新增菜单”按钮以及操作列的“编辑/删除”入口。
- **缓存刷新**：在完成写操作后调用 `menuStore.fetchRoutes({ force: true })` 或直接清空缓存版本号，确保下一次导航即刻生效。

---

## 5. 守卫与权限处理逻辑

1. **登录阶段**：`/auth/me` 接口返回用户的 `roles`、`permissions`。前端在 `userStore` 中保存这两个集合，供菜单/按钮权限判断使用。
2. **菜单渲染阶段**：
   - 若 `meta.guard` 为空，表示所有登录用户可见；
   - 若为数组，只有数组内的角色可见；
   - 若对象 `{ role: ['Editor'], mode: 'except' }`，表示这些角色被排除；`mode: 'include'` 则为白名单。
3. **按钮级别权限**：菜单管理页面可复用现有 `v-permission` 指令（或自定义 hooks），根据 `iam.menus.manage` 等权限控制按钮显示与接口调用。

---

## 6. 错误处理与边界情况

- **401/403**：跳转登录页或 403 页，并清除本地缓存的菜单/版本。
- **422**：提示表单校验错误；当删除接口返回“请先删除子菜单”时，需定位到对应节点并引导用户操作。
- **500**：提示“系统繁忙，请稍后再试”，同时记录埋点日志以便后端排查。
- **网络超时**：重试机制建议 1-2 次，超过后提示用户手动刷新。
- **组件映射失败**：若 `component` 对应的视图不存在，应在路由层 fallback 到 404 页面，并在控制台给出警告。

---

## 7. 测试计划

1. **单元测试**（可选）：对菜单树解析函数、守卫判断逻辑编写 Jest 测试。
2. **集成测试**：利用 Mock 工具（例如 `msw` 或本地 `vite-plugin-mock`）模拟接口，验证缓存、版本号、权限切换等行为。
3. **冒烟测试场景**：
   - 登录不同角色（超级管理员、普通运营）验证菜单差异；
   - 新增菜单→刷新→确认出现在路由列表；
   - 删除菜单后验证 `/routes` 接口返回的版本号是否递增；
   - `guard` 黑名单模式能否正确隐藏指定角色菜单。

---

## 8. 落地 Checklist

- [ ] 登录流程完成后调用 `/routes` 并缓存 `version`；
- [ ] 动态注册路由并渲染侧边栏菜单；
- [ ] `X-Menu-Version` 变更时重载菜单；
- [ ] 菜单管理页面实现树展示、增删改、角色/权限绑定；
- [ ] 写操作后刷新菜单缓存，确保下次访问生效；
- [ ] 处理所有错误状态与 Loading 状态；
- [ ] 完成功能冒烟和权限切换测试，确保 Apifox 文档与实现一致。

---

有任何实现上的问题，请与后端沟通确认，禁止自定义接口字段或响应结构。建议在接入前先执行 Apifox 中的示例请求，确认接口可达后再联调实际页面。祝顺利！💪
