<template>
  <div class="permission-management">
    <!-- 左侧分组树 -->
    <div class="tree-panel">
      <div class="panel-header">
        <h3>权限分组</h3>
        <el-tag type="info">{{ totalPermissions }} 个权限</el-tag>
      </div>

      <el-input
        v-model="treeSearchText"
        placeholder="搜索模块"
        clearable
        class="tree-search"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <el-tree
        ref="treeRef"
        :data="filteredTree"
        :props="treeProps"
        :highlight-current="true"
        :expand-on-click-node="false"
        node-key="key"
        default-expand-all
        @node-click="handleNodeClick"
      >
        <template #default="{ node, data }">
          <span class="custom-tree-node">
            <span class="node-label">{{ data.label }}</span>
            <el-tag size="small" type="info" round>{{ data.count }}</el-tag>
          </span>
        </template>
      </el-tree>
    </div>

    <!-- 右侧权限表 -->
    <div class="table-panel">
      <div class="panel-header">
        <h3>权限列表</h3>
        <div class="header-actions">
          <el-input
            v-model="searchKeywords"
            placeholder="搜索权限名称"
            clearable
            style="width: 240px"
            @change="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>

          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新建权限
          </el-button>
        </div>
      </div>

      <el-alert
        v-if="selectedGroup"
        :title="`当前分组：${selectedGroupLabel}`"
        type="info"
        closable
        @close="handleClearGroup"
        style="margin-bottom: 15px"
      />

      <el-table
        v-loading="loading"
        :data="permissions"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />

        <el-table-column prop="name" label="权限名称" min-width="200">
          <template #default="{ row }">
            <el-text tag="code">{{ row.name }}</el-text>
          </template>
        </el-table-column>

        <el-table-column prop="display_name" label="显示名称" min-width="180" />

        <el-table-column prop="group" label="分组" width="180">
          <template #default="{ row }">
            <el-tag>{{ row.group }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="guard_name" label="守卫" width="100">
          <template #default="{ row }">
            <el-tag type="success" size="small">{{ row.guard_name }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button
              size="small"
              type="danger"
              @click="handleDelete(row)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchPermissions"
        @current-change="fetchPermissions"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import { getPermissionGroups, getPermissions, deletePermission } from '@/api/permission'

interface TreeNode {
  key: string
  label: string
  count: number
  module?: string
  children?: TreeNode[]
}

interface Permission {
  id: number
  name: string
  display_name: string
  group: string
  guard_name: string
  metadata: any
}

// 左侧树数据
const treeRef = ref()
const groupTree = ref<TreeNode[]>([])
const treeSearchText = ref('')
const treeProps = {
  label: 'label',
  children: 'children'
}

// 右侧表格数据
const permissions = ref<Permission[]>([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)
const searchKeywords = ref('')
const selectedGroup = ref('')
const selectedGroupLabel = ref('')

// 计算总权限数
const totalPermissions = computed(() => {
  return groupTree.value.reduce((sum, module) => sum + module.count, 0)
})

// 过滤树节点
const filteredTree = computed(() => {
  if (!treeSearchText.value) {
    return groupTree.value
  }
  const searchText = treeSearchText.value.toLowerCase()
  return groupTree.value.filter(module =>
    module.label.toLowerCase().includes(searchText)
  )
})

// 加载分组树
const fetchGroupTree = async () => {
  try {
    const { data } = await getPermissionGroups()
    groupTree.value = data.tree
  } catch (error) {
    ElMessage.error('加载分组树失败')
  }
}

// 加载权限列表
const fetchPermissions = async () => {
  loading.value = true
  try {
    const { data } = await getPermissions({
      group: selectedGroup.value,
      keywords: searchKeywords.value,
      page: currentPage.value,
      per_page: pageSize.value
    })
    permissions.value = data.list
    total.value = data.total
  } catch (error) {
    ElMessage.error('加载权限列表失败')
  } finally {
    loading.value = false
  }
}

// 点击树节点
const handleNodeClick = (data: TreeNode) => {
  // 只有二级节点（有 module 字段）才筛选
  if (data.module) {
    selectedGroup.value = data.key
    selectedGroupLabel.value = `${data.module} > ${data.label}`
    currentPage.value = 1
    fetchPermissions()
  } else {
    // 点击一级节点，展开/折叠
    const node = treeRef.value.getNode(data.key)
    node.expanded = !node.expanded
  }
}

// 清除分组筛选
const handleClearGroup = () => {
  selectedGroup.value = ''
  selectedGroupLabel.value = ''
  currentPage.value = 1
  fetchPermissions()
}

// 搜索
const handleSearch = () => {
  currentPage.value = 1
  fetchPermissions()
}

// 新建权限
const handleAdd = () => {
  // TODO: 打开新建对话框
  ElMessage.info('新建权限功能待实现')
}

// 编辑权限
const handleEdit = (row: Permission) => {
  // TODO: 打开编辑对话框
  ElMessage.info(`编辑权限: ${row.name}`)
}

// 删除权限
const handleDelete = async (row: Permission) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除权限 "${row.display_name}" 吗？`,
      '删除确认',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    await deletePermission(row.id)
    ElMessage.success('删除成功')

    // 刷新列表
    fetchPermissions()
    fetchGroupTree()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 页面加载
onMounted(() => {
  fetchGroupTree()
  fetchPermissions()
})
</script>

<style scoped lang="scss">
.permission-management {
  display: flex;
  height: calc(100vh - 120px);
  gap: 20px;
  padding: 20px;
  background: #f5f7fa;

  .tree-panel {
    width: 320px;
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;

      h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
      }
    }

    .tree-search {
      margin-bottom: 15px;
    }

    .el-tree {
      flex: 1;
      overflow-y: auto;

      .custom-tree-node {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding-right: 10px;

        .node-label {
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .el-tag {
          margin-left: 8px;
          min-width: 35px;
          text-align: center;
        }
      }
    }
  }

  .table-panel {
    flex: 1;
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;

      h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
      }

      .header-actions {
        display: flex;
        gap: 10px;
      }
    }

    .el-table {
      flex: 1;
    }
  }
}

// 响应式设计
@media (max-width: 1200px) {
  .permission-management {
    flex-direction: column;
    height: auto;

    .tree-panel {
      width: 100%;
      max-height: 400px;
    }
  }
}
</style>
