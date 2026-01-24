# 登录日志功能使用指南

## 功能概述

登录日志功能自动记录所有用户的登录尝试，包括成功和失败的登录。管理员可以查看系统所有用户的登录历史，普通用户可以查看自己的登录记录。

## 主要特性

- ✅ **自动记录** - 登录时自动记录，无需手动操作
- ✅ **成功/失败** - 区分成功和失败的登录尝试
- ✅ **详细信息** - 记录IP地址、User-Agent、登录时间等
- ✅ **多维筛选** - 支持按用户、状态、IP、时间等条件筛选
- ✅ **权限控制** - 管理员可查看所有日志，用户只能查看自己的
- ✅ **失败原因** - 记录登录失败的具体原因

## 数据表结构

```sql
CREATE TABLE iam_login_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULL,              -- 用户ID
    username VARCHAR(60) NULL,         -- 用户名
    account VARCHAR(150) NULL,         -- 登录账号
    status VARCHAR(10),                -- success/failed
    failure_reason VARCHAR NULL,       -- 失败原因
    ip VARCHAR(45) NULL,               -- IP地址
    user_agent TEXT NULL,              -- User-Agent
    login_type VARCHAR(20),            -- 登录方式: password/sms/oauth/qrcode
    metadata JSONB NULL,               -- 扩展信息
    login_at TIMESTAMP,                -- 登录时间
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## API 接口

### 1. 获取登录日志列表（管理员）

```http
GET /api/iam/login-logs?page=1&per_page=20
Authorization: Bearer {token}

# 按用户筛选
GET /api/iam/login-logs?user_id=5

# 按状态筛选
GET /api/iam/login-logs?status=failed

# 按IP筛选
GET /api/iam/login-logs?ip=192.168.1.100

# 按时间范围筛选
GET /api/iam/login-logs?start_date=2026-01-01&end_date=2026-01-31

# 加载用户信息
GET /api/iam/login-logs?with_user=true

# 组合查询
GET /api/iam/login-logs?status=failed&start_date=2026-01-24&ip=192.168
```

**响应示例：**
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "user_id": 5,
        "username": "zhangsan",
        "account": "zhangsan@scenic.local",
        "status": "success",
        "status_text": "成功",
        "failure_reason": null,
        "ip": "127.0.0.1",
        "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...",
        "login_type": "password",
        "login_type_text": "密码登录",
        "metadata": {},
        "login_at": "2026-01-24 12:30:00",
        "created_at": "2026-01-24 12:30:00"
      }
    ],
    "total": 1
  }
}
```

### 2. 查看登录日志详情

```http
GET /api/iam/login-logs/1
Authorization: Bearer {token}

# 加载用户信息
GET /api/iam/login-logs/1?with_user=true
```

### 3. 获取我的登录日志

普通用户可以查看自己的登录历史：

```http
GET /api/iam/login-logs/my?page=1&per_page=20
Authorization: Bearer {token}

# 只看失败记录
GET /api/iam/login-logs/my?status=failed

# 按时间筛选
GET /api/iam/login-logs/my?start_date=2026-01-01
```

## 登录类型

系统支持多种登录方式：

| login_type | 说明 |
|-----------|------|
| password | 密码登录（默认） |
| sms | 短信验证码 |
| oauth | 第三方登录 |
| qrcode | 扫码登录 |

## 登录状态

| status | 说明 | failure_reason 示例 |
|--------|------|-------------------|
| success | 登录成功 | null |
| failed | 登录失败 | 账号或密码不正确 |
| failed | 登录失败 | 账号已被禁用 |

## 权限配置

登录日志功能需要以下权限：

```php
'iam.login-logs.view' => '查看登录日志'
```

在角色管理中为相应角色分配此权限。

## 使用场景

### 1. 安全审计

管理员可以通过登录日志监控系统安全：

```http
# 查看最近的失败登录尝试
GET /api/iam/login-logs?status=failed&sort_by=login_at&sort_order=desc

# 查看特定IP的登录记录（疑似攻击）
GET /api/iam/login-logs?ip=192.168.1.100

# 查看某个用户的登录历史
GET /api/iam/login-logs?user_id=5&with_user=true
```

### 2. 异常检测

检测异常登录行为：

```bash
# 同一账号多次失败登录
GET /api/iam/login-logs?username=admin&status=failed&start_date=2026-01-24

# 同一IP多次失败登录
GET /api/iam/login-logs?ip=192.168.1.100&status=failed
```

### 3. 用户账号管理

用户查看自己的登录历史：

```http
# 我的登录记录
GET /api/iam/login-logs/my

# 我最近的失败登录
GET /api/iam/login-logs/my?status=failed&per_page=10
```

## 前端集成示例

### Vue 3 + Element Plus

```vue
<template>
  <el-table :data="logs" border>
    <el-table-column prop="username" label="用户名" />
    <el-table-column prop="account" label="登录账号" />
    <el-table-column prop="status_text" label="状态">
      <template #default="{ row }">
        <el-tag :type="row.status === 'success' ? 'success' : 'danger'">
          {{ row.status_text }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column prop="ip" label="IP地址" />
    <el-table-column prop="login_type_text" label="登录方式" />
    <el-table-column prop="login_at" label="登录时间" />
    <el-table-column prop="failure_reason" label="失败原因" />
  </el-table>

  <el-pagination
    v-model:current-page="currentPage"
    v-model:page-size="pageSize"
    :total="total"
    @current-change="fetchLogs"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getLoginLogs } from '@/api/login-log'

const logs = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

const fetchLogs = async () => {
  const { data } = await getLoginLogs({
    page: currentPage.value,
    per_page: pageSize.value,
    with_user: true
  })
  logs.value = data.list
  total.value = data.total
}

onMounted(() => {
  fetchLogs()
})
</script>
```

## 数据清理

建议定期清理旧的登录日志以节省存储空间：

```php
// 删除 90 天前的登录日志
LoginLog::where('login_at', '<', now()->subDays(90))->delete();
```

可以通过计划任务自动执行：

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('login-logs:clean')->daily();
}
```

## 扩展建议

### 1. 登录预警

当检测到异常登录时发送通知：
- 同一IP短时间内多次失败登录
- 异地登录（IP地理位置变化）
- 非工作时间登录

### 2. IP地理位置

使用 GeoIP 库记录登录地理位置：

```php
'metadata' => [
    'location' => [
        'country' => 'China',
        'city' => 'Beijing',
    ]
]
```

### 3. 设备指纹

记录设备信息用于识别异常设备。

## 总结

登录日志功能为系统提供了完整的登录审计能力，有助于：
- 安全监控和威胁检测
- 用户行为分析
- 合规审计要求
- 故障排查和调试
