<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WeiJuKeJi\LaravelIam\Http\Requests\Permission\PermissionStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\Permission\PermissionUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\PermissionResource;
use WeiJuKeJi\LaravelIam\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:iam.permissions.view')->only(['index', 'show', 'groups']);
        $this->middleware('permission:iam.permissions.manage')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['keywords', 'guard_name', 'group', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params, 50);

        $query = Permission::query()->filter($params);

        $permissions = $query->orderBy('group')->orderBy('id')->paginate($perPage);

        return $this->respondWithPagination($permissions, PermissionResource::class);
    }

    /**
     * 获取权限分组树
     */
    public function groups(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->select('group', 'name', 'display_name')
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->get();

        // 构建树形结构
        $tree = $this->buildGroupTree($permissions);

        return $this->success([
            'tree' => $tree,
            'total' => count($tree),
        ]);
    }

    public function store(PermissionStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $permission = Permission::create($data + ['guard_name' => $data['guard_name'] ?? 'sanctum']);

        $payload = PermissionResource::make($permission)->toArray($request);

        return $this->success($payload, '权限创建成功');
    }

    public function show(Permission $permission): JsonResponse
    {
        return $this->respondWithResource($permission, PermissionResource::class);
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data)) {
            $permission->fill($data);
            $permission->save();
        }

        $payload = PermissionResource::make($permission)->toArray($request);

        return $this->success($payload, '权限更新成功');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        return $this->success(null, '权限删除成功');
    }

    /**
     * 构建分组树形结构
     */
    private function buildGroupTree($permissions): array
    {
        $groupCounts = [];
        $groupNames = [];
        $modulePermissions = []; // 用于存储每个模块下的权限，以便推断模块名称

        // 统计每个分组的权限数量
        foreach ($permissions as $permission) {
            $group = $permission->group;
            if (! isset($groupCounts[$group])) {
                $groupCounts[$group] = 0;
                $groupNames[$group] = $group;
            }
            $groupCounts[$group]++;

            // 记录模块权限，用于推断模块名
            $parts = explode('.', $group);
            $module = $parts[0];
            if (! isset($modulePermissions[$module])) {
                $modulePermissions[$module] = [];
            }
            $modulePermissions[$module][] = $permission;
        }

        // 构建树形结构（支持两级：模块.资源）
        $tree = [];
        $modules = [];

        foreach ($groupNames as $group) {
            $parts = explode('.', $group);
            $module = $parts[0]; // 第一级：如 horizon, iam, device
            $resource = $parts[1] ?? null; // 第二级：如 Stats, Users

            if (! isset($modules[$module])) {
                $modules[$module] = [
                    'key' => $module,
                    'label' => $this->formatModuleName($module, $modulePermissions[$module] ?? []),
                    'count' => 0,
                    'children' => [],
                ];
            }

            if ($resource) {
                $modules[$module]['children'][] = [
                    'key' => $group,
                    'label' => $resource,
                    'count' => $groupCounts[$group],
                    'module' => $module,
                ];
                $modules[$module]['count'] += $groupCounts[$group];
            }
        }

        // 转换为数组并排序
        foreach ($modules as $module) {
            // 对子节点按 label 排序
            usort($module['children'], function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            $tree[] = $module;
        }

        // 对一级节点按 key 排序
        usort($tree, function ($a, $b) {
            return strcmp($a['key'], $b['key']);
        });

        return $tree;
    }

    /**
     * 格式化模块名称
     * 优先级：配置文件 > 从 display_name 推断 > 首字母大写
     */
    private function formatModuleName(string $module, array $permissions): string
    {
        // 1. 优先从配置文件读取
        $configLabels = config('iam.module_labels', []);
        if (isset($configLabels[$module])) {
            return $configLabels[$module];
        }

        // 2. 从权限的 display_name 中推断模块名称
        if (! empty($permissions)) {
            $inferredName = $this->inferModuleNameFromPermissions($module, $permissions);
            if ($inferredName) {
                return $inferredName;
            }
        }

        // 3. 回退到首字母大写
        return ucfirst($module);
    }

    /**
     * 从权限 display_name 中推断模块名称
     * 例如：horizon.Stats.查看 → horizon.Stats
     *      iam.IAM - 用户管理.查看 → IAM
     */
    private function inferModuleNameFromPermissions(string $module, array $permissions): ?string
    {
        foreach ($permissions as $permission) {
            if (empty($permission->display_name)) {
                continue;
            }

            $displayName = $permission->display_name;

            // 处理格式：iam.IAM - 用户管理.查看
            // 提取第二段中的模块名（如 "IAM - 用户管理" → "IAM"）
            $parts = explode('.', $displayName);
            if (count($parts) >= 2) {
                $firstPart = trim($parts[0]);
                $secondPart = trim($parts[1]);

                if ($secondPart === '') {
                    continue;
                }

                // 如果包含 " - "，提取前半部分作为模块名
                if (str_contains($secondPart, ' - ')) {
                    $moduleName = trim(explode(' - ', $secondPart)[0]);
                    if (! empty($moduleName)) {
                        return $moduleName;
                    }
                }

                $prefix = $firstPart !== '' ? $firstPart : $module;

                return $prefix.'.'.$secondPart;
            }
        }

        return null;
    }
}
