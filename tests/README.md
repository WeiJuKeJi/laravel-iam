# 测试文档

本目录包含 Laravel IAM 扩展包的单元测试和功能测试。

## 目录结构

```
tests/
├── TestCase.php                          # 测试基类
├── Unit/                                 # 单元测试
│   ├── Models/                          # 模型测试
│   │   ├── UserTest.php                # User 模型测试
│   │   ├── RoleTest.php                # Role 模型测试
│   │   └── MenuTest.php                # Menu 模型测试
│   └── Services/                        # 服务测试
│       ├── DepartmentMoveServiceTest.php      # 部门移动服务测试
│       ├── PermissionGroupServiceTest.php     # 权限分组服务测试
│       └── MenuServiceTest.php                # 菜单服务测试
└── Feature/                             # 功能测试
    ├── PermissionApiTest.php           # 权限 API 测试
    └── MenuApiTest.php                 # 菜单 API 测试
```

## 运行测试

### 运行所有测试

```bash
vendor/bin/phpunit
```

或使用 Composer 脚本：

```bash
composer test
```

### 运行特定测试套件

**只运行单元测试：**
```bash
vendor/bin/phpunit tests/Unit
```

**只运行功能测试：**
```bash
vendor/bin/phpunit tests/Feature
```

### 运行单个测试文件

```bash
vendor/bin/phpunit tests/Unit/Models/UserTest.php
```

### 运行单个测试方法

```bash
vendor/bin/phpunit --filter it_can_create_a_user
```

### 查看代码覆盖率

```bash
vendor/bin/phpunit --coverage-html coverage
```

然后在浏览器中打开 `coverage/index.html`

## 测试覆盖

当前测试覆盖以下模块：

### 模型测试 (Unit/Models/)

- **UserTest**: 用户模型
  - 创建用户
  - 密码自动哈希
  - 角色和权限关联
  - 软删除功能
  - 元数据序列化

- **RoleTest**: 角色模型
  - 创建角色
  - 权限关联和同步
  - 用户模型解析

- **MenuTest**: 菜单模型
  - 创建菜单
  - 父子关系
  - 树结构构建
  - 可见性检查（super-admin, 公共菜单, 角色菜单）

### 服务测试 (Unit/Services/)

- **DepartmentMoveServiceTest**: 部门移动服务
  - 移动到目标之前/之后
  - 移动到父部门内部
  - 移动到根部门
  - 异常处理（目标不存在, 移动到自身, 移动到子部门）

- **PermissionGroupServiceTest**: 权限分组服务
  - 构建分组树结构
  - 模块名称格式化（配置 > 推断 > 默认）
  - 树形排序
  - 权限计数

- **MenuServiceTest**: 菜单服务
  - super-admin 权限
  - 角色过滤
  - 公共菜单
  - 禁用菜单过滤
  - 树结构构建
  - 缓存机制

### 功能测试 (Feature/)

- **PermissionApiTest**: 权限 API
  - 认证要求
  - 权限检查
  - 列表查询
  - 分组查询

- **MenuApiTest**: 菜单 API
  - 认证要求
  - 权限检查
  - CRUD 操作
  - 树结构查询
  - 子菜单删除限制

## 编写新测试

### 1. 创建测试类

继承 `WeiJuKeJi\LaravelIam\Tests\TestCase`:

```php
<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Models;

use WeiJuKeJi\LaravelIam\Tests\TestCase;

class YourModelTest extends TestCase
{
    /** @test */
    public function it_does_something(): void
    {
        // 编写测试
    }
}
```

### 2. 使用测试数据

```php
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password',
]);

$this->assertDatabaseHas('users', [
    'email' => 'test@example.com',
]);
```

### 3. 测试异常

```php
$this->expectException(YourException::class);
$this->expectExceptionMessage('Expected message');

// 执行会抛出异常的代码
```

### 4. API 测试

```php
$response = $this->actingAs($user, 'sanctum')
    ->postJson('/api/endpoint', ['data' => 'value']);

$response->assertStatus(200)
    ->assertJson(['key' => 'value']);
```

## 测试数据库

测试使用内存 SQLite 数据库 (`:memory:`)，每个测试执行前会自动：
1. 创建新的数据库
2. 运行迁移
3. 测试结束后清理

不需要手动设置测试数据库。

## 最佳实践

1. **每个测试只测试一件事**
2. **使用描述性的测试方法名** (`it_can_create_a_user` 比 `test_create` 更好)
3. **遵循 AAA 模式**: Arrange (准备), Act (执行), Assert (断言)
4. **使用工厂类创建测试数据** (如果有的话)
5. **测试边界情况和异常**
6. **保持测试独立**: 每个测试不应依赖其他测试

## CI/CD 集成

可以在 CI/CD 流程中添加：

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: vendor/bin/phpunit
```

## 需要更多测试覆盖的模块

- Department 模型测试
- Permission 模型详细测试
- Controller 测试
- Middleware 测试
- Request 验证测试
- Resource 转换测试

欢迎贡献更多测试！
