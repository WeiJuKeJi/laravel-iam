<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WeiJuKeJi\LaravelIam\Exceptions\DepartmentException;
use WeiJuKeJi\LaravelIam\Exceptions\DepartmentMoveException;
use WeiJuKeJi\LaravelIam\Http\Requests\Department\DepartmentStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\Department\DepartmentUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\DepartmentResource;
use WeiJuKeJi\LaravelIam\Models\Department;
use WeiJuKeJi\LaravelIam\Services\DepartmentMoveService;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:iam.departments.view')->only(['index', 'show', 'tree', 'ancestors', 'descendants']);
        $this->middleware('permission:iam.departments.manage')->only(['store', 'update', 'destroy', 'move']);
    }

    /**
     * 获取部门列表（平铺）
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->all();
        $perPage = $this->resolvePerPage($params);

        $query = Department::query()->with('manager');

        // 应用筛选
        if (isset($params['filter'])) {
            $query->filter($params['filter']);
        }

        // 排序
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $departments = $query->paginate($perPage);

        return $this->respondWithPagination($departments, DepartmentResource::class);
    }

    /**
     * 获取部门树形结构
     */
    public function tree(Request $request): JsonResponse
    {
        $params = $request->only([
            'name',
            'code',
            'status',
            'manager_id',
            'parent_id',
        ]);

        $query = Department::filter($params);

        // 预加载 children 和 manager 关系
        $query->with(['children', 'manager']);

        // 只查询启用的部门
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // 获取所有部门并统计总数（在 toTree() 之前）
        $departments = $query->get();
        $total = $departments->count();

        // 使用 nestedset 的 toTree() 方法构建树形结构
        $tree = $departments->toTree();

        $payload = [
            'list' => DepartmentResource::collection($tree)->toArray($request),
            'total' => $total,
        ];

        return $this->success($payload);
    }

    /**
     * 创建部门
     */
    public function store(DepartmentStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $department = Department::create($data);
        $department->load('manager');

        $payload = DepartmentResource::make($department)->toArray($request);

        return $this->success($payload, '部门创建成功');
    }

    /**
     * 查看部门详情
     */
    public function show(Request $request, Department $department): JsonResponse
    {
        $relations = ['manager', 'parent', 'children'];

        // 按需加载祖先部门
        if ($request->boolean('with_ancestors')) {
            $relations[] = 'ancestors';
        }

        // 按需加载后代部门
        if ($request->boolean('with_descendants')) {
            $relations[] = 'descendants';
        }

        $department->load($relations);

        return $this->respondWithResource($department, DepartmentResource::class);
    }

    /**
     * 更新部门
     */
    public function update(DepartmentUpdateRequest $request, Department $department): JsonResponse
    {
        $data = $request->validated();

        // 检查是否试图将部门移动到其子部门下（会造成循环）
        if (isset($data['parent_id']) && $data['parent_id']) {
            $newParent = Department::find($data['parent_id']);
            if ($newParent && $department->isAncestorOf($newParent)) {
                return $this->error('不能将部门移动到其子部门下', 422, [], 422);
            }
        }

        $department->update($data);
        $department->load('manager');

        $payload = DepartmentResource::make($department)->toArray($request);

        return $this->success($payload, '部门更新成功');
    }

    /**
     * 删除部门
     */
    public function destroy(Department $department): JsonResponse
    {
        try {
            // 检查是否有子部门
            if ($department->children()->exists()) {
                throw DepartmentException::hasChildren();
            }

            // 检查是否有员工
            if ($department->users()->exists()) {
                throw DepartmentException::hasUsers();
            }

            $department->delete();

            return $this->success(null, '部门删除成功');
        } catch (DepartmentException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 移动部门
     */
    public function move(Request $request, Department $department): JsonResponse
    {
        $request->validate([
            'parent_id' => 'nullable|integer|exists:' . $department->getTable() . ',id',
            'position' => 'required|in:before,after,inside',
            'target_id' => 'required_unless:position,inside|integer|exists:' . $department->getTable() . ',id',
        ]);

        try {
            // 使用 Service 执行移动操作
            app(DepartmentMoveService::class)->move(
                $department,
                $request->input('position'),
                $request->input('target_id'),
                $request->input('parent_id')
            );

            // 刷新数据并加载关系
            $department->fresh()->load('manager');
            $payload = DepartmentResource::make($department)->toArray($request);

            return $this->success($payload, '部门移动成功');
        } catch (DepartmentMoveException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }
}
