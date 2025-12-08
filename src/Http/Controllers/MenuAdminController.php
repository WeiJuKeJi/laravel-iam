<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use WeiJuKeJi\LaravelIam\Http\Requests\Menu\MenuStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\Menu\MenuUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\MenuResource;
use WeiJuKeJi\LaravelIam\Models\Menu;

class MenuAdminController extends Controller
{
    public function __construct(private readonly DatabaseManager $db)
    {
        $this->middleware('permission:iam.menus.view')->only(['index', 'show', 'tree']);
        $this->middleware('permission:iam.menus.manage')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['parent_id', 'name', 'path', 'is_enabled', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params);

        $records = Menu::filter($params)
            ->with(['roles:id,name', 'permissions:id,name'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);

        return $this->respondWithPagination($records, MenuResource::class);
    }

    public function tree(Request $request): JsonResponse
    {
        $params = $request->only(['parent_id', 'name', 'path', 'is_enabled']);

        $menus = Menu::filter($params)
            ->with(['roles:id,name', 'permissions:id,name'])
            ->get();

        $tree = Menu::buildTree($menus);

        $payload = [
            'list' => MenuResource::collection($tree)->toArray($request),
            'total' => $menus->count(),
        ];

        return $this->success($payload);
    }

    public function store(MenuStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $roles = Arr::pull($data, 'role_ids', []);
        $permissions = Arr::pull($data, 'permission_ids', []);

        // 缓存清除由 Menu 模型的 saved 事件自动处理
        $menu = $this->db->transaction(function () use ($data, $roles, $permissions) {
            $menu = Menu::create($data);

            if (! empty($roles)) {
                $menu->roles()->sync($roles);
            }

            if (! empty($permissions)) {
                $menu->permissions()->sync($permissions);
            }

            return $menu->fresh(['roles:id,name', 'permissions:id,name', 'children']);
        });

        $payload = MenuResource::make($menu)->toArray($request);

        return $this->success($payload, '菜单创建成功');
    }

    public function show(Menu $menu): JsonResponse
    {
        $menu->load(['roles:id,name', 'permissions:id,name', 'children']);

        return $this->respondWithResource($menu, MenuResource::class);
    }

    public function update(MenuUpdateRequest $request, Menu $menu): JsonResponse
    {
        $data = $request->validated();

        $roles = Arr::pull($data, 'role_ids', []);
        $permissions = Arr::pull($data, 'permission_ids', []);

        // 缓存清除由 Menu 模型的 saved 事件自动处理
        $menu = $this->db->transaction(function () use ($menu, $data, $roles, $permissions) {
            $menu->fill($data);
            $menu->save();

            $menu->roles()->sync($roles);
            $menu->permissions()->sync($permissions);

            return $menu->fresh(['roles:id,name', 'permissions:id,name', 'children']);
        });

        $payload = MenuResource::make($menu)->toArray($request);

        return $this->success($payload, '菜单更新成功');
    }

    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->children()->exists()) {
            return $this->error('请先删除子菜单', 422);
        }

        // 缓存清除由 Menu 模型的 deleted 事件自动处理
        $menu->delete();

        return $this->success(null, '菜单已删除');
    }
}
