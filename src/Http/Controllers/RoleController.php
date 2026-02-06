<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use WeiJuKeJi\LaravelIam\Http\Requests\Role\RoleStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\Role\RoleUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\RoleResource;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Services\MenuService;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:iam.roles.view')->only(['index', 'show']);
        $this->middleware('permission:iam.roles.manage')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['keywords', 'guard_name', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params);

        $query = Role::query()->filter($params);

        if ($request->boolean('with_permissions')) {
            $query->with('permissions');
        }

        if ($request->boolean('with_menus')) {
            $query->with('menus');
        }

        if ($request->boolean('with_users_count')) {
            $query->withCount('users');
        }

        $roles = $query->orderBy('id')->paginate($perPage);

        return $this->respondWithPagination($roles, RoleResource::class);
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $permissions = Arr::pull($data, 'permissions', []);
        $menuIds = Arr::pull($data, 'menu_ids', []);

        $role = Role::create($data + ['guard_name' => $data['guard_name'] ?? 'sanctum']);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        if (! empty($menuIds)) {
            $role->menus()->sync($menuIds);
            app(MenuService::class)->flushCache();
        }

        $role->load('permissions');

        $payload = RoleResource::make($role)->toArray($request);

        return $this->success($payload, '角色创建成功');
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        if ($request->boolean('with_permissions')) {
            $role->loadMissing('permissions');
        }

        if ($request->boolean('with_menus')) {
            $role->loadMissing('menus');
        }

        return $this->respondWithResource($role, RoleResource::class);
    }

    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();
        $permissions = Arr::pull($data, 'permissions');
        $menuIds = Arr::pull($data, 'menu_ids');

        if (! empty($data)) {
            $role->fill($data);
            $role->save();
        }

        if (! is_null($permissions)) {
            $role->syncPermissions($permissions);
        }

        if (! is_null($menuIds)) {
            $role->menus()->sync($menuIds);
            app(MenuService::class)->flushCache();
        }

        $role->load('permissions');

        $payload = RoleResource::make($role)->toArray($request);

        return $this->success($payload, '角色更新成功');
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return $this->error('系统预置角色禁止删除', 422);
        }

        $role->delete();

        return $this->success(null, '角色删除成功');
    }
}
