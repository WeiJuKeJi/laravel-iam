<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use WeiJuKeJi\LaravelIam\Http\Requests\Role\RoleStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\Role\RoleUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\RoleResource;
use WeiJuKeJi\LaravelIam\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['keywords', 'guard_name', 'per_page', 'page']);

        $query = Role::query()->filter($params);

        if ($request->boolean('with_permissions')) {
            $query->with('permissions');
        }

        $perPage = $this->resolvePerPage($params);

        $roles = $query->orderBy('id')->paginate($perPage);

        return $this->respondWithPagination($roles, RoleResource::class);
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $permissions = Arr::pull($data, 'permissions', []);

        $role = Role::create($data + ['guard_name' => $data['guard_name'] ?? 'sanctum']);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
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

        return $this->respondWithResource($role, RoleResource::class);
    }

    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();
        $permissions = Arr::pull($data, 'permissions');

        if (! empty($data)) {
            $role->fill($data);
            $role->save();
        }

        if (! is_null($permissions)) {
            $role->syncPermissions($permissions);
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
