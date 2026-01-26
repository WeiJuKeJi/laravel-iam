<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use WeiJuKeJi\LaravelIam\Http\Requests\User\UserStoreRequest;
use WeiJuKeJi\LaravelIam\Http\Requests\User\UserUpdateRequest;
use WeiJuKeJi\LaravelIam\Http\Resources\UserResource;
use WeiJuKeJi\LaravelIam\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:iam.users.view')->only(['index', 'show']);
        $this->middleware('permission:iam.users.manage')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        // 只保留通用参数，user_type 是通用的，merchant_id 是业务特定的
        $params = $request->only(['status', 'keywords', 'email', 'username', 'role', 'department_id', 'user_type', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params);

        $query = User::query()->filter($params);

        if ($request->boolean('with_roles')) {
            $query->with('roles.permissions');
        }

        if ($request->boolean('with_permissions')) {
            $query->with('permissions');
        }

        if ($request->boolean('with_department')) {
            $query->with('department');
        }

        $users = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->respondWithPagination($users, UserResource::class);
    }

    /**
     * 创建用户
     *
     * @param UserStoreRequest $request
     */
    public function store($request): JsonResponse
    {
        $data = $request->validated();
        $roles = Arr::pull($data, 'roles', []);
        $data['status'] = $data['status'] ?? 'active';

        $user = User::create($data);

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        $user->load(['roles.permissions', 'permissions', 'department']);

        $payload = UserResource::make($user)->toArray($request);

        return $this->success($payload, '用户创建成功');
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $relations = [];
        if ($request->boolean('with_roles')) {
            $relations[] = 'roles.permissions';
        }
        if ($request->boolean('with_permissions')) {
            $relations[] = 'permissions';
        }
        if ($request->boolean('with_department')) {
            $relations[] = 'department';
        }

        if (! empty($relations)) {
            $user->loadMissing($relations);
        }

        return $this->respondWithResource($user, UserResource::class);
    }

    /**
     * 更新用户
     *
     * @param UserUpdateRequest $request
     * @param User $user
     */
    public function update($request, User $user): JsonResponse
    {
        $data = $request->validated();
        $roles = Arr::pull($data, 'roles');

        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $user->save();

        if (! is_null($roles)) {
            $user->syncRoles($roles);
        }

        $user->load(['roles.permissions', 'permissions', 'department']);

        $payload = UserResource::make($user)->toArray($request);

        return $this->success($payload, '用户更新成功');
    }

    public function destroy(User $user): JsonResponse
    {
        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, '用户删除成功');
    }
}
