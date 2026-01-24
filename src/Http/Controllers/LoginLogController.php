<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WeiJuKeJi\LaravelIam\Http\Resources\LoginLogResource;
use WeiJuKeJi\LaravelIam\Models\LoginLog;

class LoginLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:iam.login-logs.view')->only(['index', 'show']);
    }

    /**
     * 获取登录日志列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only([
            'user_id',
            'username',
            'account',
            'status',
            'ip',
            'login_type',
            'start_date',
            'end_date',
            'per_page',
            'page',
        ]);

        $perPage = $this->resolvePerPage($params);

        $query = LoginLog::query()->filter($params);

        if ($request->boolean('with_user')) {
            $query->with('user');
        }

        $logs = $query->orderByDesc('login_at')->paginate($perPage);

        return $this->respondWithPagination($logs, LoginLogResource::class);
    }

    /**
     * 查看登录日志详情
     */
    public function show(Request $request, LoginLog $loginLog): JsonResponse
    {
        if ($request->boolean('with_user')) {
            $loginLog->load('user');
        }

        return $this->respondWithResource($loginLog, LoginLogResource::class);
    }

    /**
     * 获取当前用户的登录日志
     */
    public function myLogs(Request $request): JsonResponse
    {
        $params = $request->only(['status', 'ip', 'login_type', 'start_date', 'end_date', 'per_page', 'page']);
        $perPage = $this->resolvePerPage($params);

        $query = LoginLog::query()
            ->where('user_id', $request->user()->id)
            ->filter($params);

        $logs = $query->orderByDesc('login_at')->paginate($perPage);

        return $this->respondWithPagination($logs, LoginLogResource::class);
    }
}
