<?php

namespace WeiJuKeJi\LaravelIam\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'iam:sync-permissions
                            {--prefix=* : 指定要同步的路由前缀，可多次使用。不指定则使用配置文件中的默认值}
                            {--all : 同步所有符合条件的路由，忽略前缀限制}
                            {--dry-run : 仅展示将要同步的权限，不写入数据库}';

    protected $description = '根据命名规范从路由集中生成并同步权限';

    public function handle(Router $router): int
    {
        $config = config('iam') ?? [];

        $syncAll = $this->option('all');

        // 优先使用命令行传入的前缀，否则使用配置文件
        $routePrefixes = $syncAll ? [] : ($this->option('prefix') ?: ($config['route_prefixes'] ?? ['iam']));

        $ignoreRoutes = $config['ignore_routes'] ?? [];
        $actionMap = $config['action_map'] ?? [];
        $actionLabels = $config['action_labels'] ?? [];
        $groupLabels = $config['group_labels'] ?? [];
        $guard = $config['guard'] ?? 'sanctum';

        $dryRun = (bool) $this->option('dry-run');

        // 显示正在扫描的前缀
        if (!$dryRun) {
            if ($syncAll) {
                $this->info('扫描模式：同步所有路由（不限前缀）');
            } else {
                $this->info('扫描路由前缀：' . implode(', ', $routePrefixes));
            }
        }

        $permissionCandidates = [];

        foreach ($router->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || in_array($name, $ignoreRoutes, true)) {
                continue;
            }

            $segments = explode('.', $name);

            if (count($segments) < 3) {
                continue;
            }

            $module = array_shift($segments);

            if ($module === 'api' && ! empty($segments)) {
                $module = array_shift($segments);
            }

            // 如果不是同步所有，则检查前缀
            if (!$syncAll && (! in_array($module, $routePrefixes, true) || empty($segments))) {
                continue;
            }

            // 同步所有模式下，仍需要有资源和动作
            if ($syncAll && empty($segments)) {
                continue;
            }

            $actionRaw = array_pop($segments);
            [$resourcePath, $resourceKey] = $this->resolveResourceSegments($segments, $route, $module);

            $normalizedAction = $actionMap[$actionRaw] ?? Str::slug($actionRaw, '_');

            $permissionName = sprintf('%s.%s.%s', $module, $resourcePath, $normalizedAction);

            $group = $groupLabels[$module.'.'.$resourcePath]
                ?? $groupLabels[$module.'.'.$resourceKey]
                ?? $groupLabels[$resourcePath]
                ?? $groupLabels[$resourceKey]
                ?? null;

            if (! $group) {
                $defaultGroup = Str::replace(' ', '', Str::headline($resourceKey));
                $group = sprintf('%s.%s', $module, $defaultGroup);
            } elseif (! Str::contains($group, '.')) {
                $group = sprintf('%s.%s', $module, $group);
            }

            $actionLabel = $actionLabels[$normalizedAction] ?? Str::headline($normalizedAction);

            $displayName = $group.'.'.$actionLabel;

            $permissionCandidates[$permissionName] = [
                'name' => $permissionName,
                'guard_name' => $guard,
                'group' => $group,
                'display_name' => $displayName,
            ];
        }

        if (empty($permissionCandidates)) {
            $this->warn('未发现符合规则的路由。');

            return static::SUCCESS;
        }

        $this->info('待同步的权限数：'.count($permissionCandidates));

        if ($dryRun) {
            foreach ($permissionCandidates as $candidate) {
                $this->line(sprintf('- %s (%s)', $candidate['name'], $candidate['display_name']));
            }

            return static::SUCCESS;
        }

        $existingPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', array_keys($permissionCandidates))
            ->get()
            ->keyBy('name');

        $created = 0;
        $updated = 0;

        foreach ($permissionCandidates as $name => $payload) {
            /** @var Permission|null $permission */
            $permission = $existingPermissions->get($name);

            if (! $permission) {
                $permission = Permission::query()
                    ->where('name', $payload['name'])
                    ->where('guard_name', $payload['guard_name'])
                    ->first();
            }

            if ($permission) {
                $permission->fill(Arr::only($payload, ['display_name', 'group', 'guard_name']));

                if ($permission->isDirty()) {
                    $permission->save();
                    ++$updated;
                }

                continue;
            }

            try {
                Permission::create($payload);
                ++$created;
            } catch (PermissionAlreadyExists $e) {
                $permission = Permission::query()
                    ->where('name', $payload['name'])
                    ->where('guard_name', $payload['guard_name'])
                    ->first();

                if ($permission) {
                    $permission->fill(Arr::only($payload, ['display_name', 'group', 'guard_name']));
                    if ($permission->isDirty()) {
                        $permission->save();
                        ++$updated;
                    }
                    continue;
                }

                throw $e;
            }
        }

        $this->info(sprintf('同步完成：新增 %d 条，更新 %d 条。', $created, $updated));

        $this->syncRoles(array_keys($permissionCandidates), $guard, $config);

        return static::SUCCESS;
    }

    protected function syncRoles(array $permissionNames, string $guard, array $config): void
    {
        $roleNames = $config['sync_roles'] ?? ['super-admin', 'Admin'];
        $roles = Role::query()->whereIn('name', $roleNames)->where('guard_name', $guard)->get();

        if ($roles->isEmpty()) {
            $this->warn('未找到需要同步权限的角色。');

            return;
        }

        $permissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissionNames)
            ->get();

        foreach ($roles as $role) {
            $role->syncPermissions($permissions->pluck('name')->all());
            $this->line(sprintf('角色 %s 已同步 %d 个权限。', $role->name, $permissions->count()));
        }
    }

    protected function resolveResourceSegments(array $segments, Route $route, string $module): array
    {
        if (! empty($segments)) {
            $resourcePath = implode('.', $segments);
            $resourceKey = explode('.', $resourcePath)[0] ?? $resourcePath;

            return [$resourcePath, $resourceKey];
        }

        $uri = $route->uri();
        $uri = preg_replace('#^api/#', '', $uri) ?? $uri;
        $uri = preg_replace('#^v\d+/#', '', $uri) ?? $uri;

        if (Str::startsWith($uri, $module.'/')) {
            $candidate = substr($uri, strlen($module) + 1);
            if ($candidate !== '' && ! Str::startsWith($candidate, '{')) {
                $uri = $candidate;
            }
        }

        $uri = trim($uri, '/');
        $resourceGuess = strtok($uri, '/') ?: $module;
        $resourceGuess = trim($resourceGuess, '{}');

        if ($resourceGuess === '') {
            $resourceGuess = $module;
        }

        return [$resourceGuess, Str::before($resourceGuess, '.') ?: $resourceGuess];
    }
}
