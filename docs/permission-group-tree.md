# 权限分组树功能文档

## 功能概述

支持按模块和资源将权限进行分组管理，前端使用**左树右表**的方式展示：
- **左侧树**：显示权限分组的层级结构（模块 → 资源）
- **右侧表**：显示选中分组下的权限列表

---

## API 接口

### 1. 获取权限分组树

**接口地址**：`GET /api/iam/permissions/groups`

**权限要求**：`iam.permissions.view`

**响应示例**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "tree": [
      {
        "key": "device",
        "label": "Device 设备管理",
        "count": 13,
        "children": [
          {
            "key": "device.Kiosks",
            "label": "Kiosks",
            "count": 5,
            "module": "device"
          },
          {
            "key": "device.Gates",
            "label": "Gates",
            "count": 6,
            "module": "device"
          }
        ]
      },
      {
        "key": "iam",
        "label": "IAM 权限管理",
        "count": 20,
        "children": [
          {
            "key": "iam.Users",
            "label": "Users",
            "count": 10,
            "module": "iam"
          }
        ]
      }
    ],
    "total": 10
  }
}
```

**字段说明**：
- `key`：分组唯一标识（用于筛选）
- `label`：分组显示名称
- `count`：该分组下的权限数量
- `children`：子分组列表
- `module`：所属模块

---

### 2. 按分组筛选权限

**接口地址**：`GET /api/iam/permissions`

**权限要求**：`iam.permissions.view`

**请求参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| group | string | 否 | 权限分组 key，如 `device.Kiosks` |
| keywords | string | 否 | 搜索关键词 |
| guard_name | string | 否 | 守卫名称 |
| per_page | int | 否 | 每页数量，默认 50 |
| page | int | 否 | 页码 |

**请求示例**：
```bash
GET /api/iam/permissions?group=device.Kiosks&page=1&per_page=20
```

**响应示例**：
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [
      {
        "id": 31,
        "name": "device.kiosks.view",
        "display_name": "device.Kiosks.查看",
        "group": "device.Kiosks",
        "guard_name": "sanctum",
        "metadata": null,
        "created_at": "2026-01-24 09:17:16",
        "updated_at": "2026-01-24 09:17:16"
      }
    ],
    "total": 5
  }
}
```

---

## 前端实现建议

### 1. 页面布局

```vue
<template>
  <div class="permission-management">
    <!-- 左侧分组树 -->
    <div class="tree-panel">
      <el-tree
        :data="groupTree"
        :props="{ label: 'label', children: 'children' }"
        node-key="key"
        @node-click="handleNodeClick"
      >
        <template #default="{ node, data }">
          <span class="tree-node">
            <span>{{ data.label }}</span>
            <span class="count">{{ data.count }}</span>
          </span>
        </template>
      </el-tree>
    </div>

    <!-- 右侧权限表 -->
    <div class="table-panel">
      <el-table :data="permissions" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="权限名称" />
        <el-table-column prop="display_name" label="显示名称" />
        <el-table-column prop="group" label="分组" />
        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <el-button @click="handleEdit(row)">编辑</el-button>
            <el-button @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        @current-change="fetchPermissions"
      />
    </div>
  </div>
</template>
```

### 2. 数据交互

```typescript
import { ref, onMounted } from 'vue'
import { getPermissionGroups, getPermissions } from '@/api/permission'

export default {
  setup() {
    const groupTree = ref([])
    const permissions = ref([])
    const loading = ref(false)
    const currentPage = ref(1)
    const pageSize = ref(20)
    const total = ref(0)
    const selectedGroup = ref('')

    // 加载分组树
    const fetchGroupTree = async () => {
      const { data } = await getPermissionGroups()
      groupTree.value = data.tree
    }

    // 加载权限列表
    const fetchPermissions = async () => {
      loading.value = true
      try {
        const { data } = await getPermissions({
          group: selectedGroup.value,
          page: currentPage.value,
          per_page: pageSize.value
        })
        permissions.value = data.list
        total.value = data.total
      } finally {
        loading.value = false
      }
    }

    // 点击树节点
    const handleNodeClick = (data) => {
      // 只有二级节点（资源分组）才筛选
      if (data.module) {
        selectedGroup.value = data.key
        currentPage.value = 1
        fetchPermissions()
      }
    }

    onMounted(() => {
      fetchGroupTree()
      fetchPermissions()
    })

    return {
      groupTree,
      permissions,
      loading,
      currentPage,
      pageSize,
      total,
      handleNodeClick,
      fetchPermissions
    }
  }
}
```

### 3. API 封装

```typescript
// api/permission.ts
import request from '@/utils/request'

export const getPermissionGroups = () => {
  return request.get('/api/iam/permissions/groups')
}

export const getPermissions = (params) => {
  return request.get('/api/iam/permissions', { params })
}
```

---

## 样式建议

```scss
.permission-management {
  display: flex;
  height: calc(100vh - 100px);
  gap: 20px;

  .tree-panel {
    width: 280px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    overflow-y: auto;

    .tree-node {
      display: flex;
      justify-content: space-between;
      width: 100%;

      .count {
        color: #909399;
        font-size: 12px;
      }
    }
  }

  .table-panel {
    flex: 1;
    display: flex;
    flex-direction: column;

    .el-pagination {
      margin-top: 20px;
      justify-content: flex-end;
    }
  }
}
```

---

## 模块名称配置

如果需要添加新的模块，可以在 `PermissionController.php` 的 `formatModuleName()` 方法中添加：

```php
private function formatModuleName(string $module): string
{
    $names = [
        'horizon' => 'Horizon 队列监控',
        'iam' => 'IAM 权限管理',
        'device' => 'Device 设备管理',
        'common' => 'Common 公共模块',
        'voucher' => 'Voucher 兑换券',
        'theater' => 'Theater 剧场管理',
        'business' => 'Business 营业',
        'finance' => 'Finance 财务',
        'distribution' => 'Distribution 分销',
        'mdm' => 'MDM 主数据',
        'system' => 'System 系统',
        // 添加新模块
        'your_module' => 'Your Module 显示名称',
    ];

    return $names[$module] ?? ucfirst($module);
}
```

---

## 交互流程

```
1. 页面加载
   ↓
2. 获取分组树（GET /api/iam/permissions/groups）
   ↓
3. 展示左侧树形结构
   ↓
4. 用户点击树节点（如 device.Kiosks）
   ↓
5. 调用权限列表 API（GET /api/iam/permissions?group=device.Kiosks）
   ↓
6. 右侧表格显示该分组下的权限
```

---

## 注意事项

1. **权限控制**：需要 `iam.permissions.view` 权限才能访问这些接口
2. **路由顺序**：`permissions/groups` 路由必须在 `permissions/{permission}` 之前定义
3. **分组命名规范**：建议使用 `模块.资源` 的格式，如 `device.Kiosks`
4. **树形层级**：当前支持两级（模块 → 资源），如需更多层级需要修改 `buildGroupTree()` 方法
5. **排序**：分组树和子节点都已按字母排序
