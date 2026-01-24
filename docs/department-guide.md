# 部门管理模块使用指南

## 功能特性

- ✅ **无限层级** - 基于 Nested Set 模型，支持任意层级的部门树
- ✅ **高效查询** - 优化的树形结构查询性能
- ✅ **部门负责人** - 支持设置部门负责人
- ✅ **部门编码** - 唯一的部门编码标识
- ✅ **状态管理** - 启用/禁用状态控制
- ✅ **灵活移动** - 支持部门的拖拽移动
- ✅ **关联查询** - 查询祖先部门、后代部门
- ✅ **元数据扩展** - 灵活的 JSON 元数据字段

## 数据表结构

```sql
CREATE TABLE iam_departments (
    id BIGINT PRIMARY KEY,
    parent_id BIGINT NULL,
    _lft INT NOT NULL,          -- Nested Set 左值
    _rgt INT NOT NULL,          -- Nested Set 右值
    name VARCHAR(100),          -- 部门名称
    code VARCHAR(50) UNIQUE,    -- 部门编码
    manager_id BIGINT NULL,     -- 部门负责人
    sort_order INT DEFAULT 0,   -- 排序
    status VARCHAR(20),         -- active/inactive
    description TEXT,           -- 描述
    metadata JSONB,             -- 扩展元数据
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES iam_departments(id) ON DELETE SET NULL
);
```

## API 路由

### 基础 CRUD

| 方法 | 路由 | 说明 |
|------|------|------|
| GET | `/api/iam/departments` | 获取部门列表（平铺） |
| GET | `/api/iam/departments/tree` | 获取部门树形结构 |
| POST | `/api/iam/departments` | 创建部门 |
| GET | `/api/iam/departments/{id}` | 查看部门详情 |
| PUT | `/api/iam/departments/{id}` | 更新部门 |
| DELETE | `/api/iam/departments/{id}` | 删除部门 |

### 高级功能

| 方法 | 路由 | 说明 |
|------|------|------|
| POST | `/api/iam/departments/{id}/move` | 移动部门位置 |
| GET | `/api/iam/departments/{id}/ancestors` | 获取祖先部门 |
| GET | `/api/iam/departments/{id}/descendants` | 获取后代部门 |

## 使用示例

### 1. 获取部门列表（平铺）

```http
GET /api/iam/departments?per_page=20&sort_by=sort_order&sort_order=asc

# 筛选
GET /api/iam/departments?filter[status]=active&filter[name]=技术部
```

**响应**：
```json
{
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "name": "总公司",
      "code": "COMPANY",
      "manager_id": 1,
      "manager": {
        "id": 1,
        "name": "张三",
        "username": "zhangsan"
      },
      "sort_order": 0,
      "status": "active",
      "description": "公司总部",
      "metadata": {},
      "full_path": "总公司",
      "level": 0,
      "is_leaf": false,
      "created_at": "2024-01-23 10:00:00",
      "updated_at": "2024-01-23 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 35
  }
}
```

### 2. 获取部门树形结构

```http
GET /api/iam/departments/tree

# 只获取启用的部门
GET /api/iam/departments/tree?active_only=1

# 按名称筛选
GET /api/iam/departments/tree?name=技术部

# 按状态筛选
GET /api/iam/departments/tree?status=active

# 组合查询
GET /api/iam/departments/tree?status=active&name=技术
```

**说明**：树形接口默认会加载负责人信息（manager）。

**响应**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "name": "总公司",
        "code": "COMPANY",
        "manager_id": 1,
        "manager": {
          "id": 1,
          "name": "张三",
          "username": "zhangsan"
        },
        "sort_order": 0,
        "status": "active",
        "description": "公司总部",
        "metadata": {},
        "level": 0,
        "is_leaf": false,
        "children": [
          {
            "id": 2,
            "name": "技术部",
            "code": "TECH",
            "sort_order": 1,
            "status": "active",
            "level": 1,
            "is_leaf": false,
            "children": [
              {
                "id": 3,
                "name": "研发中心",
                "code": "TECH-DEV",
                "sort_order": 1,
                "status": "active",
                "level": 2,
                "is_leaf": true,
                "children": []
              }
            ]
          }
        ]
      }
    ],
    "total": 1
  }
}
```

### 3. 创建部门

```http
POST /api/iam/departments
Content-Type: application/json

{
  "parent_id": 1,
  "name": "市场部",
  "code": "MARKET",
  "manager_id": 5,
  "sort_order": 4,
  "status": "active",
  "description": "市场营销部门",
  "metadata": {
    "budget": 1000000,
    "location": "北京"
  }
}
```

### 4. 更新部门

```http
PUT /api/iam/departments/2
Content-Type: application/json

{
  "name": "技术研发部",
  "manager_id": 3,
  "description": "负责产品研发和技术创新"
}
```

### 5. 移动部门

将部门移动到另一个位置：

```http
POST /api/iam/departments/3/move
Content-Type: application/json

# 移动到某个部门之前
{
  "position": "before",
  "target_id": 4
}

# 移动到某个部门之后
{
  "position": "after",
  "target_id": 4
}

# 移动到某个部门内部（作为子部门）
{
  "position": "inside",
  "parent_id": 2
}
```

### 6. 删除部门

```http
DELETE /api/iam/departments/5
```

**注意**：
- 有子部门的部门无法删除
- 有员工的部门无法删除

### 7. 查看部门详情（按需加载关联）

```http
# 基础信息
GET /api/iam/departments/3

# 加载祖先部门（用于面包屑导航）
GET /api/iam/departments/3?with_ancestors=1

# 加载后代部门（用于预览影响范围）
GET /api/iam/departments/2?with_descendants=1

# 同时加载祖先和后代
GET /api/iam/departments/2?with_ancestors=1&with_descendants=1
```

**响应示例（加载祖先）**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "id": 3,
    "name": "研发中心",
    "code": "TECH-DEV",
    "full_path": "总公司 / 技术部 / 研发中心",
    "ancestors": [
      {
        "id": 1,
        "name": "总公司",
        "code": "COMPANY",
        "level": 0
      },
      {
        "id": 2,
        "name": "技术部",
        "code": "TECH",
        "level": 1
      }
    ]
  }
}
```

## 模型使用

### 基本查询

```php
use WeiJuKeJi\LaravelIam\Models\Department;

// 查询所有部门
$departments = Department::all();

// 查询启用的部门
$activeDepts = Department::active()->get();

// 查询根部门
$roots = Department::roots()->get();

// 查询某个部门的子部门
$children = $dept->children;

// 查询某个部门的父部门
$parent = $dept->parent;
```

### 树形结构查询

```php
// 获取完整的部门树
$tree = Department::get()->toTree();

// 获取某个部门的祖先
$ancestors = $dept->ancestors()->get();

// 获取某个部门的后代
$descendants = $dept->descendants()->get();

// 判断是否为叶子节点
if ($dept->isLeaf()) {
    // 没有子部门
}

// 获取部门层级
$level = $dept->level; // 0, 1, 2...

// 获取部门全路径
$fullPath = $dept->full_path; // "总公司 / 技术部 / 研发中心"
```

### 部门操作

```php
// 创建根部门
$company = Department::create([
    'name' => '总公司',
    'code' => 'COMPANY',
]);

// 创建子部门
$tech = Department::create([
    'parent_id' => $company->id,
    'name' => '技术部',
    'code' => 'TECH',
]);

// 或使用关联创建
$sales = $company->children()->create([
    'name' => '销售部',
    'code' => 'SALES',
]);

// 移动部门
$dept->parent_id = $newParentId;
$dept->save();

// 删除部门（会级联删除所有子部门）
$dept->delete();
```

### 关联查询

```php
// 查询部门负责人
$manager = $dept->manager;

// 查询部门员工
$employees = $dept->users;

// 预加载关联
$departments = Department::with(['manager', 'users'])->get();
```

## 前端集成示例

### Vue 3 + Element Plus

```vue
<template>
  <el-tree
    :data="departmentTree"
    :props="treeProps"
    node-key="id"
    :expand-on-click-node="false"
    draggable
    @node-drop="handleNodeDrop"
  >
    <template #default="{ node, data }">
      <span class="custom-tree-node">
        <span>{{ data.name }} ({{ data.code }})</span>
        <span v-if="data.manager">
          <el-tag size="small">{{ data.manager.name }}</el-tag>
        </span>
        <span class="node-actions">
          <el-button size="small" @click="editDepartment(data)">编辑</el-button>
          <el-button size="small" type="danger" @click="deleteDepartment(data)">删除</el-button>
        </span>
      </span>
    </template>
  </el-tree>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getDepartmentTree, moveDepartment } from '@/api/department'

const departmentTree = ref([])
const treeProps = {
  children: 'children',
  label: 'name'
}

onMounted(async () => {
  const { data } = await getDepartmentTree()
  departmentTree.value = data
})

const handleNodeDrop = async (draggingNode, dropNode, dropType) => {
  const data = {
    position: dropType === 'inner' ? 'inside' : dropType === 'before' ? 'before' : 'after',
    target_id: dropNode.data.id,
  }

  if (dropType === 'inner') {
    data.parent_id = dropNode.data.id
  }

  await moveDepartment(draggingNode.data.id, data)
}
</script>
```

## 权限控制

在路由中添加权限中间件：

```php
// 在 routes/api.php 中
Route::middleware(['permission:iam.departments.view'])->group(function () {
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/tree', [DepartmentController::class, 'tree']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
});

Route::middleware(['permission:iam.departments.manage'])->group(function () {
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::put('departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);
    Route::post('departments/{department}/move', [DepartmentController::class, 'move']);
});
```

## 常见问题

### 1. 如何防止部门循环引用？

控制器中已经做了检查，不允许将部门移动到其子部门下。

### 2. 删除部门时如何处理员工？

默认情况下，有员工的部门无法删除。你可以：
- 先将员工调动到其他部门
- 或修改控制器逻辑，将员工的 department_id 设为 null

### 3. 如何优化大量部门的查询性能？

- 使用 Nested Set 模型已经提供了很好的查询性能
- 合理使用缓存
- 使用 `with()` 预加载关联关系

### 4. 如何自定义部门元数据？

使用 `metadata` 字段存储 JSON 数据：

```php
$department->metadata = [
    'budget' => 1000000,
    'location' => '北京',
    'headcount' => 50,
];
$department->save();
```

## 总结

部门管理模块提供了完整的企业组织架构管理功能，基于高效的 Nested Set 模型实现无限层级的部门树，适合各类企业级应用。
