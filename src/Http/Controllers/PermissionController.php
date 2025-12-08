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
        $this->middleware('permission:iam.permissions.view')->only(['index', 'show']);
        $this->middleware('permission:iam.permissions.manage')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['keywords', 'guard_name', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params, 50);

        $query = Permission::query()->filter($params);

        $permissions = $query->orderBy('id')->paginate($perPage);

        return $this->respondWithPagination($permissions, PermissionResource::class);
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
}
