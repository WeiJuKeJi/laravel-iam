<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Support\Collection;

/**
 * 权限分组服务
 *
 * 负责权限的分组、树形结构构建和模块名称推断
 */
class PermissionGroupService
{
    /**
     * 构建分组树形结构
     *
     * @param  Collection  $permissions  权限集合
     * @return array 树形结构数组
     */
    public function buildGroupTree(Collection $permissions): array
    {
        $groupMetadata = $this->extractGroupMetadata($permissions);
        $moduleGroups = $this->groupByModule($groupMetadata);

        return $this->sortTree($moduleGroups);
    }

    /**
     * 提取分组元数据
     */
    protected function extractGroupMetadata(Collection $permissions): array
    {
        $groupCounts = [];
        $groupNames = [];
        $modulePermissions = [];

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

        return [
            'groupCounts' => $groupCounts,
            'groupNames' => $groupNames,
            'modulePermissions' => $modulePermissions,
        ];
    }

    /**
     * 按模块分组
     */
    protected function groupByModule(array $metadata): array
    {
        $groupCounts = $metadata['groupCounts'];
        $groupNames = $metadata['groupNames'];
        $modulePermissions = $metadata['modulePermissions'];

        $modules = [];

        foreach ($groupNames as $group) {
            [$module, $resource] = $this->parseGroupKey($group);

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

        return $modules;
    }

    /**
     * 解析分组键
     *
     * @return array [module, resource]
     */
    protected function parseGroupKey(string $group): array
    {
        $parts = explode('.', $group);
        $module = $parts[0]; // 第一级：如 horizon, iam, device
        $resource = $parts[1] ?? null; // 第二级：如 Stats, Users

        return [$module, $resource];
    }

    /**
     * 格式化模块名称
     * 优先级：配置文件 > 从 display_name 推断 > 首字母大写
     */
    protected function formatModuleName(string $module, array $permissions): string
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
    protected function inferModuleNameFromPermissions(string $module, array $permissions): ?string
    {
        foreach ($permissions as $permission) {
            if (empty($permission->display_name)) {
                continue;
            }

            $displayName = $permission->display_name;

            // 处理格式：iam.IAM - 用户管理.查看
            // 提取第二段中的模块名（如 "IAM - 用户管理" → "IAM"）
            $parts = explode('.', $displayName);

            if (count($parts) < 2) {
                continue;
            }

            $firstPart = trim($parts[0]);
            $secondPart = trim($parts[1]);

            if ($secondPart === '') {
                continue;
            }

            // 如果包含 " - "，提取前半部分作为模块名
            if (str_contains($secondPart, ' - ')) {
                // 限制分割次数为 2，避免索引越界
                $moduleName = trim(explode(' - ', $secondPart, 2)[0]);
                if (! empty($moduleName)) {
                    return $moduleName;
                }
            }

            $prefix = $firstPart !== '' ? $firstPart : $module;

            return $prefix.'.'.$secondPart;
        }

        return null;
    }

    /**
     * 对树形结构排序
     */
    protected function sortTree(array $modules): array
    {
        $tree = [];

        // 对子节点按 label 排序
        foreach ($modules as $module) {
            usort($module['children'], fn ($a, $b) => strcmp($a['label'], $b['label']));
            $tree[] = $module;
        }

        // 对一级节点按 key 排序
        usort($tree, fn ($a, $b) => strcmp($a['key'], $b['key']));

        return $tree;
    }
}
