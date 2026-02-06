# 菜单路由能力说明

本文档描述 IAM 模块中菜单路由体系的业务背景、数据模型、缓存策略及接口用法，便于前后端协同构建统一的导航/权限体验。

---

## 1. 设计目标与数据模型

- **统一菜单来源**：所有前端路由节点都落库至 `menus` 表，保持可视化维护与权限联动。字段要点如下：
  - `parent_id`：父节点 ID，`null` 表示根节点；采用普通 parent-child 结构，按 `sort_order` 排序。
  - `path`/`component`/`redirect`：对应前端路由配置，`component` 允许为空（多用于布局节点）。
  - `meta`：JSON 字段，承载标题、图标、徽标、是否缓存等全部页面属性。
  - `is_public`：布尔值，标记为公共菜单后所有登录用户均可见。
- **多对多绑定**：
  - `menu_role` 用于将菜单与 Spatie Permission 的角色建立绑定，实现按角色控制菜单可见性。
- **缓存保障**：`Menu` 模型在 `saved` / `deleted` 事件中调用 `MenuService::flushCache()`；写操作后会立即清理缓存标签（或在不支持标签的驱动上全量清理），防止旧数据影响路由渲染。

## 2. 服务端核心逻辑

1. **构建菜单树**：`Menu::buildTree()` 按 `parent_id + sort_order + id` 排序，将平铺数据转成树形集合。
2. **权限过滤**：`MenuService::getMenuTreeForUser()` 会收集当前用户的角色，并逐节点调用 `Menu::isVisibleFor()`：
   - 未启用的节点直接剔除；
   - `super-admin` 角色可见全部菜单；
   - 公共菜单（`is_public = true`）对所有登录用户可见；
   - 若节点绑定了角色但当前用户不在绑定列表内，则直接剔除；
   - 若父节点自身无权限但存在可见子节点，会保留父节点以保证前端路由结构完整。
3. **版本号生成**：根据菜单集合最近更新时间和节点总数生成 MD5，形成 `version`；接口响应头同时返回 `X-Menu-Version`，便于前端缓存对比。
4. **缓存策略**：默认缓存 30 分钟；若缓存驱动支持标签（如 Redis），将使用 `menus` 标签集中清理；否则退化为按键清理。所有写操作及种子脚本执行后都会主动刷新。

## 3. 接口一览与使用流程

### 3.1 前端路由获取

| 接口 | 方法 | 说明 |
| --- | --- | --- |
| `/api/v1/iam/routes` | `GET` | 登录后拉取当前用户可见的菜单路由。 |

- **鉴权**：`auth:sanctum`，需携带 `Authorization: Bearer {token}`；
- **请求头**：`Accept: application/json`；
- **响应体**：

```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [
      { "path": "/", "name": "Root", "component": "Layout", "meta": { "title": "首页" }, "children": [...] }
    ],
    "total": 1,
    "version": "2b7fc1f7c16b4f8c"
  }
}
```

- **响应头**：`X-Menu-Version: 2b7fc1f7c16b4f8c`
- **调用流程**：
  1. 前端登录成功后立即请求该接口，缓存 `list` 及 `version`；
  2. 后续每次进入系统可携带本地缓存版本号，若响应头发生变化则更新本地缓存；
  3. 若需要强制刷新，可在前端提供"菜单重载"按钮，直接重新请求接口。

### 3.2 菜单后台管理

| 接口 | 方法 | 权限 | 用途 |
| --- | --- | --- | --- |
| `/api/v1/iam/menus` | `GET` | `iam.menus.view` | 查看完整菜单树（含角色绑定）。 |
| `/api/v1/iam/menus` | `POST` | `iam.menus.manage` | 创建菜单节点。 |
| `/api/v1/iam/menus/{menu}` | `GET` | `iam.menus.view` | 查看指定菜单详情。 |
| `/api/v1/iam/menus/{menu}` | `PUT` | `iam.menus.manage` | 更新菜单节点及绑定关系。 |
| `/api/v1/iam/menus/{menu}` | `DELETE` | `iam.menus.manage` | 删除菜单节点（无子节点时）。 |

- **创建/更新请求体关键字段**：
  - `parent_id`：父节点 ID，`null` 表示根节点；
  - `name`/`path`/`component`/`redirect`：与前端路由配置一致；
  - `sort_order`：数值小的排前；
  - `meta`：如 `{"title":"报表中心","icon":"bar-chart-2-line","noKeepAlive":true}`；
  - `role_ids`：数组形式的角色绑定 ID。
- **删除校验**：若节点仍存在子菜单，接口会返回 422 提示"请先删除子菜单"。
- **后台操作推荐流程**：
  1. 通过 `GET /menus` 获取当前树结构，提供拖拽/排序/编辑界面；
  2. 提交新增或修改时调用 `POST/PUT`，成功后提示用户刷新前端路由；
  3. 删除节点前确认其无子节点，并提示前端缓存需要刷新；
  4. 操作完成后可调用 `GET /routes` 验证权限过滤是否符合预期。

## 4. 菜单访问控制

1. **接口级**：菜单管理接口通过 `iam.menus.view` / `iam.menus.manage` 权限做 RBAC 控制；路由获取接口只要求登录态。
2. **菜单级**：
   - 未启用的菜单不可见；
   - `super-admin` 角色可见全部菜单；
   - 公共菜单（`is_public = true`）对所有登录用户可见；
   - 绑定了角色的菜单（`menu_role`）只对拥有相应角色的用户可见；
   - 未绑定任何角色且非公共的菜单不可见。
3. **与 Spatie Permission 的配合**：菜单通过 `menu_role` 中间表与角色关联，角色编辑页可管理"绑定菜单"，菜单编辑页可管理"绑定角色"。

## 5. 缓存与版本控制

- **缓存实现**：`MenuService` 默认缓存 30 分钟；如使用 Redis 等支持标签的存储，会使用 `menus` 标签集中清理；否则退化为按键清理。
- **刷新时机**：
  - 调用菜单管理 API 的增删改操作后，系统自动刷新缓存；
  - 修改角色的菜单绑定（`menu_role`）后也会刷新缓存；
  - Seeder（`MenuSeeder`）执行完毕后也会刷新；
  - 如需要手动刷新，可调用 `MenuService::flushCache()`。
- **版本字段**：
  - `version` 由 `max(updated_at)` 和节点数量生成的哈希值构成；
  - 当任意节点数据变化或绑定关系更新时，版本值会随之变化；
  - 前端可基于该值判断本地路由缓存是否过期。

## 6. 推荐运维流程

1. 使用 `php artisan migrate` 同步菜单表结构。
2. 执行 `php artisan module:seed Iam` 导入默认菜单/权限数据（需保证数据库连接可用）。
3. 首次部署后，通知前端获取最新 `apifox.json` 文件，本地生成文档并联调 `/routes` 和 `/menus` 接口。
4. 如需大规模调整菜单结构，建议先在测试环境演练，确认版本号与缓存刷新行为符合预期后再推广到生产。

---

通过以上机制，系统实现了"菜单即权限"的闭环：后端统一维护菜单结构与访问约束，前端按需渲染路由并结合版本号做缓存控制，确保导航体验与权限体系始终一致。
