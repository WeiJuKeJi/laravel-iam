# 菜单管理前端对接指南

本文档面向前端开发者，提供 Laravel IAM 菜单管理模块的完整对接指南。

---

## 1. API 接口说明

### 1.1 基础信息

- **Base URL**: `/api/iam`（可通过 `config('iam.route_prefix')` 配置）
- **认证方式**: Bearer Token（Sanctum）
- **请求头**:
  ```
  Authorization: Bearer {token}
  Accept: application/json
  Content-Type: application/json
  ```

### 1.2 接口列表

| 接口 | 方法 | 权限 | 说明 |
|------|------|------|------|
| `/api/iam/routes` | GET | 登录即可 | 获取当前用户的菜单路由树 |
| `/api/iam/menus` | GET | `iam.menus.view` | 获取完整菜单列表（分页） |
| `/api/iam/menus/tree` | GET | `iam.menus.view` | 获取完整菜单树 |
| `/api/iam/menus` | POST | `iam.menus.manage` | 创建菜单 |
| `/api/iam/menus/{id}` | GET | `iam.menus.view` | 获取菜单详情 |
| `/api/iam/menus/{id}` | PUT | `iam.menus.manage` | 更新菜单 |
| `/api/iam/menus/{id}` | DELETE | `iam.menus.manage` | 删除菜单 |

---

## 2. 数据结构

### 2.1 菜单节点结构

```typescript
interface MenuNode {
  id: number
  parent_id: number | null
  name: string                    // 路由名称，需唯一
  path: string                    // 路由路径
  component: string | null        // 组件路径
  redirect: string | null         // 重定向地址
  sort_order: number              // 排序值，越小越靠前
  is_enabled: boolean             // 是否启用
  meta: MenuMeta                  // 路由元信息
  guard: string[] | GuardConfig   // 守卫配置
  roles: string[]                 // 关联角色名称
  permissions: string[]           // 关联权限名称
  children: MenuNode[]            // 子菜单
}

interface MenuMeta {
  title: string                   // 菜单标题
  icon?: string                   // 图标
  hidden?: boolean                // 是否隐藏
  noCache?: boolean               // 不缓存
  affix?: boolean                 // 固定标签
  breadcrumb?: boolean            // 显示面包屑
  target?: '_blank' | '_self'     // 链接打开方式（外链用）
  dynamicNewTab?: boolean         // 动态新标签页（iframe 用）
  permissions?: string[]          // 前端权限验证
  [key: string]: any              // 其他自定义字段
}

interface GuardConfig {
  role: string[]
  mode: 'include' | 'except'      // include=白名单, except=黑名单
}
```

### 2.2 创建/更新请求体

```typescript
interface MenuInput {
  parent_id?: number | null
  name: string                    // 必填，唯一
  path: string                    // 必填
  component?: string | null
  redirect?: string | null
  sort_order?: number             // 默认 0
  is_enabled?: boolean            // 默认 true
  meta?: Record<string, any>
  guard?: string[] | GuardConfig
  role_ids?: number[]             // 关联角色 ID
  permission_ids?: number[]       // 关联权限 ID
}
```

### 2.3 API 响应格式

```typescript
// 成功响应
interface SuccessResponse<T> {
  code: number      // 200
  msg: string       // "success"
  data: T
}

// 列表响应
interface ListResponse<T> {
  code: number
  msg: string
  data: {
    list: T[]
    total: number
  }
}

// 错误响应
interface ErrorResponse {
  code: number      // 400/422/500
  msg: string       // 错误信息
  errors?: Record<string, string[]>  // 422 时的字段错误
}
```

---

## 3. 菜单类型说明

### 3.1 普通路由菜单

标准的系统内部页面路由：

```json
{
  "name": "UserList",
  "path": "users",
  "component": "system/users/index",
  "meta": {
    "title": "用户管理",
    "icon": "user",
    "permissions": ["iam.users.view"]
  }
}
```

### 3.2 布局/目录菜单

作为父级容器，通常不对应具体页面：

```json
{
  "name": "System",
  "path": "/system",
  "component": "Layout",
  "redirect": "/system/users",
  "meta": {
    "title": "系统管理",
    "icon": "setting"
  },
  "children": [...]
}
```

### 3.3 外链菜单

跳转到外部网站，在新标签页打开：

```json
{
  "name": "ExternalLink",
  "path": "//github.com/your-repo",
  "component": null,
  "meta": {
    "title": "GitHub",
    "icon": "external-link-line",
    "target": "_blank"
  }
}
```

**前端判断逻辑**：
```typescript
function isExternalLink(path: string): boolean {
  return /^(https?:|mailto:|tel:|\/\/)/.test(path)
}
```

### 3.4 内嵌网页（Iframe）

在系统内嵌入第三方页面：

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
      "component": "/@/views/iframe/index.vue",
      "meta": {
        "title": "Iframe",
        "dynamicNewTab": true,
        "hidden": true
      }
    },
    {
      "name": "BaiduDoc",
      "path": "view?url=www.baidu.com&title=百度",
      "component": null,
      "meta": {
        "title": "百度"
      }
    }
  ]
}
```

**前端 Iframe 组件示例**：
```vue
<template>
  <div class="iframe-container">
    <iframe :src="iframeSrc" frameborder="0"></iframe>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const iframeSrc = computed(() => {
  const url = route.query.url as string
  if (!url) return ''
  // 自动补全协议
  return url.startsWith('http') ? url : `https://${url}`
})
</script>

<style scoped>
.iframe-container {
  width: 100%;
  height: calc(100vh - 84px);
}
.iframe-container iframe {
  width: 100%;
  height: 100%;
}
</style>
```

---

## 4. 动态路由加载

### 4.1 加载流程

```
用户登录 → 获取 Token → 调用 /routes → 解析菜单 → 动态注册路由 → 渲染侧边栏
```

### 4.2 Pinia Store 示例

```typescript
// stores/menu.ts
import { defineStore } from 'pinia'
import { getRoutes, getMenuTree } from '@/api/menu'

interface MenuState {
  routes: MenuNode[]
  menus: MenuNode[]
  isLoaded: boolean
}

export const useMenuStore = defineStore('menu', {
  state: (): MenuState => ({
    routes: [],
    menus: [],
    isLoaded: false
  }),

  actions: {
    // 获取用户路由（用于动态路由）
    async fetchRoutes(force = false) {
      if (this.isLoaded && !force) return this.routes

      const res = await getRoutes()
      this.routes = res.data.list
      this.isLoaded = true
      return this.routes
    },

    // 获取完整菜单树（用于菜单管理）
    async fetchMenuTree() {
      const res = await getMenuTree()
      this.menus = res.data.list
      return this.menus
    },

    // 重置状态
    resetMenu() {
      this.routes = []
      this.menus = []
      this.isLoaded = false
    }
  }
})
```

### 4.3 路由解析函数

```typescript
// utils/route-parser.ts
import type { RouteRecordRaw } from 'vue-router'

// 组件映射表
const componentModules = import.meta.glob('@/views/**/*.vue')

// 布局组件
const Layout = () => import('@/layout/index.vue')

export function parseRoutes(menus: MenuNode[]): RouteRecordRaw[] {
  return menus.map(menu => {
    const route: RouteRecordRaw = {
      path: menu.path,
      name: menu.name,
      meta: {
        title: menu.meta?.title,
        icon: menu.meta?.icon,
        hidden: menu.meta?.hidden,
        ...menu.meta
      },
      children: []
    }

    // 处理组件
    if (menu.component === 'Layout') {
      route.component = Layout
    } else if (menu.component) {
      const componentPath = `/src/views/${menu.component}.vue`
      route.component = componentModules[componentPath]
    }

    // 处理重定向
    if (menu.redirect) {
      route.redirect = menu.redirect
    }

    // 递归处理子菜单
    if (menu.children?.length) {
      route.children = parseRoutes(menu.children)
    }

    return route
  })
}
```

### 4.4 路由守卫

```typescript
// router/permission.ts
import router from './index'
import { useUserStore } from '@/stores/user'
import { useMenuStore } from '@/stores/menu'
import { parseRoutes } from '@/utils/route-parser'

const whiteList = ['/login', '/404', '/403']

router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()
  const menuStore = useMenuStore()

  // 白名单直接放行
  if (whiteList.includes(to.path)) {
    return next()
  }

  // 未登录跳转登录页
  if (!userStore.token) {
    return next(`/login?redirect=${to.path}`)
  }

  // 已加载路由直接放行
  if (menuStore.isLoaded) {
    return next()
  }

  try {
    // 获取用户信息和菜单
    await userStore.fetchUserInfo()
    const menus = await menuStore.fetchRoutes()

    // 解析并注册动态路由
    const routes = parseRoutes(menus)
    routes.forEach(route => {
      router.addRoute('Layout', route)
    })

    // 添加 404 兜底路由
    router.addRoute({
      path: '/:pathMatch(.*)*',
      redirect: '/404'
    })

    // 重新导航到目标页面
    next({ ...to, replace: true })
  } catch (error) {
    userStore.logout()
    next('/login')
  }
})
```

---

## 5. 菜单管理页面

### 5.1 API 封装

```typescript
// api/menu.ts
import request from '@/utils/request'

// 获取用户路由
export function getRoutes() {
  return request.get('/api/iam/routes')
}

// 获取菜单树
export function getMenuTree(params?: { is_enabled?: boolean }) {
  return request.get('/api/iam/menus/tree', { params })
}

// 获取菜单列表（分页）
export function getMenuList(params?: {
  parent_id?: number
  name?: string
  is_enabled?: boolean
  per_page?: number
  page?: number
}) {
  return request.get('/api/iam/menus', { params })
}

// 获取菜单详情
export function getMenuDetail(id: number) {
  return request.get(`/api/iam/menus/${id}`)
}

// 创建菜单
export function createMenu(data: MenuInput) {
  return request.post('/api/iam/menus', data)
}

// 更新菜单
export function updateMenu(id: number, data: MenuInput) {
  return request.put(`/api/iam/menus/${id}`, data)
}

// 删除菜单
export function deleteMenu(id: number) {
  return request.delete(`/api/iam/menus/${id}`)
}
```

### 5.2 菜单表单组件

```vue
<template>
  <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
    <el-form-item label="父级菜单" prop="parent_id">
      <el-tree-select
        v-model="form.parent_id"
        :data="menuTree"
        :props="{ label: 'name', value: 'id' }"
        placeholder="根菜单"
        clearable
        check-strictly
      />
    </el-form-item>

    <el-form-item label="菜单类型">
      <el-radio-group v-model="menuType" @change="handleTypeChange">
        <el-radio value="route">页面路由</el-radio>
        <el-radio value="directory">目录</el-radio>
        <el-radio value="external">外链</el-radio>
        <el-radio value="iframe">内嵌网页</el-radio>
      </el-radio-group>
    </el-form-item>

    <el-form-item label="路由名称" prop="name">
      <el-input v-model="form.name" placeholder="唯一标识，如 UserList" />
    </el-form-item>

    <el-form-item label="路由路径" prop="path">
      <el-input v-model="form.path" :placeholder="pathPlaceholder" />
    </el-form-item>

    <el-form-item v-if="showComponent" label="组件路径" prop="component">
      <el-input v-model="form.component" placeholder="如 system/users/index" />
    </el-form-item>

    <el-form-item v-if="menuType === 'iframe'" label="内嵌地址">
      <el-input v-model="iframeUrl" placeholder="如 www.example.com" />
    </el-form-item>

    <el-form-item label="菜单标题" prop="meta.title">
      <el-input v-model="form.meta.title" placeholder="显示名称" />
    </el-form-item>

    <el-form-item label="图标" prop="meta.icon">
      <el-input v-model="form.meta.icon" placeholder="图标名称" />
    </el-form-item>

    <el-form-item label="排序">
      <el-input-number v-model="form.sort_order" :min="0" :max="9999" />
    </el-form-item>

    <el-form-item label="是否启用">
      <el-switch v-model="form.is_enabled" />
    </el-form-item>

    <el-form-item label="是否隐藏">
      <el-switch v-model="form.meta.hidden" />
    </el-form-item>

    <el-form-item label="关联角色">
      <el-select v-model="form.role_ids" multiple placeholder="选择角色">
        <el-option
          v-for="role in roles"
          :key="role.id"
          :label="role.display_name || role.name"
          :value="role.id"
        />
      </el-select>
    </el-form-item>

    <el-form-item label="关联权限">
      <el-select v-model="form.permission_ids" multiple placeholder="选择权限">
        <el-option
          v-for="perm in permissions"
          :key="perm.id"
          :label="perm.display_name || perm.name"
          :value="perm.id"
        />
      </el-select>
    </el-form-item>
  </el-form>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  modelValue: MenuInput
  menuTree: MenuNode[]
  roles: Role[]
  permissions: Permission[]
}>()

const emit = defineEmits(['update:modelValue'])

const form = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const menuType = ref('route')
const iframeUrl = ref('')

const showComponent = computed(() =>
  ['route', 'directory'].includes(menuType.value)
)

const pathPlaceholder = computed(() => {
  switch (menuType.value) {
    case 'external': return '//github.com/your-repo'
    case 'iframe': return 'view?url=xxx&title=xxx'
    default: return '/system/users'
  }
})

function handleTypeChange(type: string) {
  switch (type) {
    case 'directory':
      form.value.component = 'Layout'
      break
    case 'external':
      form.value.component = null
      form.value.meta.target = '_blank'
      break
    case 'iframe':
      form.value.meta.dynamicNewTab = true
      break
    default:
      delete form.value.meta.target
      delete form.value.meta.dynamicNewTab
  }
}

// 监听 iframe URL 变化，自动更新 path
watch(iframeUrl, (url) => {
  if (menuType.value === 'iframe' && url) {
    form.value.path = `view?url=${encodeURIComponent(url)}&title=${form.value.meta.title || ''}`
  }
})

const rules = {
  name: [{ required: true, message: '请输入路由名称', trigger: 'blur' }],
  path: [{ required: true, message: '请输入路由路径', trigger: 'blur' }],
  'meta.title': [{ required: true, message: '请输入菜单标题', trigger: 'blur' }]
}
</script>
```

### 5.3 菜单列表页面

```vue
<template>
  <div class="menu-management">
    <!-- 操作栏 -->
    <div class="action-bar">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增菜单
      </el-button>
      <el-button @click="handleRefresh">
        <el-icon><Refresh /></el-icon>
        刷新
      </el-button>
    </div>

    <!-- 菜单树表格 -->
    <el-table
      :data="menuTree"
      row-key="id"
      :tree-props="{ children: 'children' }"
      v-loading="loading"
    >
      <el-table-column prop="name" label="路由名称" width="200" />
      <el-table-column prop="meta.title" label="菜单标题" width="150" />
      <el-table-column prop="path" label="路由路径" min-width="200" />
      <el-table-column prop="component" label="组件" width="200" />
      <el-table-column prop="sort_order" label="排序" width="80" align="center" />
      <el-table-column label="状态" width="80" align="center">
        <template #default="{ row }">
          <el-tag :type="row.is_enabled ? 'success' : 'info'">
            {{ row.is_enabled ? '启用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="类型" width="100" align="center">
        <template #default="{ row }">
          <el-tag v-if="isExternalLink(row.path)" type="warning">外链</el-tag>
          <el-tag v-else-if="row.meta?.dynamicNewTab" type="info">内嵌</el-tag>
          <el-tag v-else-if="row.component === 'Layout'">目录</el-tag>
          <el-tag v-else type="success">页面</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
          <el-button link type="primary" @click="handleAddChild(row)">添加子菜单</el-button>
          <el-popconfirm
            title="确定删除此菜单？"
            @confirm="handleDelete(row)"
          >
            <template #reference>
              <el-button link type="danger" :disabled="row.children?.length > 0">
                删除
              </el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px">
      <MenuForm
        v-model="formData"
        :menu-tree="menuTree"
        :roles="roles"
        :permissions="permissions"
      />
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import {
  getMenuTree,
  createMenu,
  updateMenu,
  deleteMenu
} from '@/api/menu'
import { getRoleList } from '@/api/role'
import { getPermissionList } from '@/api/permission'
import { useMenuStore } from '@/stores/menu'
import MenuForm from './components/MenuForm.vue'

const menuStore = useMenuStore()

const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('')
const menuTree = ref<MenuNode[]>([])
const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const formData = ref<MenuInput>(getDefaultForm())
const editingId = ref<number | null>(null)

function getDefaultForm(): MenuInput {
  return {
    parent_id: null,
    name: '',
    path: '',
    component: '',
    redirect: '',
    sort_order: 0,
    is_enabled: true,
    meta: { title: '', icon: '' },
    guard: [],
    role_ids: [],
    permission_ids: []
  }
}

function isExternalLink(path: string): boolean {
  return /^(https?:|mailto:|tel:|\/\/)/.test(path)
}

async function fetchData() {
  loading.value = true
  try {
    const [menuRes, roleRes, permRes] = await Promise.all([
      getMenuTree(),
      getRoleList({ per_page: 100 }),
      getPermissionList({ per_page: 200 })
    ])
    menuTree.value = menuRes.data.list
    roles.value = roleRes.data.list
    permissions.value = permRes.data.list
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  editingId.value = null
  formData.value = getDefaultForm()
  dialogTitle.value = '新增菜单'
  dialogVisible.value = true
}

function handleAddChild(parent: MenuNode) {
  editingId.value = null
  formData.value = { ...getDefaultForm(), parent_id: parent.id }
  dialogTitle.value = `新增子菜单 - ${parent.meta?.title}`
  dialogVisible.value = true
}

function handleEdit(row: MenuNode) {
  editingId.value = row.id
  formData.value = {
    parent_id: row.parent_id,
    name: row.name,
    path: row.path,
    component: row.component,
    redirect: row.redirect,
    sort_order: row.sort_order,
    is_enabled: row.is_enabled,
    meta: { ...row.meta },
    guard: row.guard,
    role_ids: [], // 需要从详情接口获取
    permission_ids: []
  }
  dialogTitle.value = '编辑菜单'
  dialogVisible.value = true
}

async function handleDelete(row: MenuNode) {
  if (row.children?.length) {
    ElMessage.warning('请先删除子菜单')
    return
  }
  try {
    await deleteMenu(row.id)
    ElMessage.success('删除成功')
    await fetchData()
    // 刷新路由缓存
    await menuStore.fetchRoutes(true)
  } catch (error: any) {
    ElMessage.error(error.message || '删除失败')
  }
}

async function handleSubmit() {
  submitting.value = true
  try {
    if (editingId.value) {
      await updateMenu(editingId.value, formData.value)
      ElMessage.success('更新成功')
    } else {
      await createMenu(formData.value)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    await fetchData()
    // 刷新路由缓存
    await menuStore.fetchRoutes(true)
  } catch (error: any) {
    ElMessage.error(error.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

function handleRefresh() {
  fetchData()
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.menu-management {
  padding: 20px;
}
.action-bar {
  margin-bottom: 16px;
}
</style>
```

---

## 6. 错误处理

### 6.1 HTTP 状态码

| 状态码 | 说明 | 处理方式 |
|--------|------|----------|
| 200 | 成功 | 正常处理 |
| 401 | 未认证 | 跳转登录页 |
| 403 | 无权限 | 显示无权限提示 |
| 422 | 验证失败 | 显示字段错误信息 |
| 500 | 服务器错误 | 显示通用错误提示 |

### 6.2 业务错误

```typescript
// 删除菜单时
if (response.code === 422 && response.msg.includes('子菜单')) {
  ElMessage.warning('请先删除子菜单')
}
```

---

## 7. 最佳实践

1. **缓存管理**：菜单数据变更后，务必调用 `menuStore.fetchRoutes(true)` 强制刷新
2. **权限控制**：根据用户权限控制按钮显示，使用 `v-permission` 指令
3. **组件懒加载**：使用 `import.meta.glob` 实现路由组件懒加载
4. **外链判断**：统一使用 `isExternalLink()` 函数判断
5. **iframe 安全**：内嵌第三方页面时注意 CSP 和 X-Frame-Options 限制

---

## 8. Checklist

- [ ] 登录后正确加载动态路由
- [ ] 侧边栏正确渲染菜单树
- [ ] 外链菜单在新标签页打开
- [ ] 内嵌网页正常显示
- [ ] 菜单增删改查功能正常
- [ ] 菜单变更后路由即时刷新
- [ ] 权限控制正确生效
- [ ] 错误状态正确处理
