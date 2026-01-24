<?php

use Illuminate\Support\Facades\Route;
use WeiJuKeJi\LaravelIam\Http\Controllers\AuthController;
use WeiJuKeJi\LaravelIam\Http\Controllers\DepartmentController;
use WeiJuKeJi\LaravelIam\Http\Controllers\LoginLogController;
use WeiJuKeJi\LaravelIam\Http\Controllers\MenuController;
use WeiJuKeJi\LaravelIam\Http\Controllers\MenuAdminController;
use WeiJuKeJi\LaravelIam\Http\Controllers\PermissionController;
use WeiJuKeJi\LaravelIam\Http\Controllers\RoleController;
use WeiJuKeJi\LaravelIam\Http\Controllers\UserController;

Route::middleware(['api'])
    ->prefix(config('iam.route_prefix', 'api/iam'))
    ->name('iam.')
    ->group(function () {
        // 登录接口添加速率限制：每分钟最多 5 次
        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('auth.login');

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::get('routes', [MenuController::class, 'index'])->name('routes.index');

            Route::get('menus/tree', [MenuAdminController::class, 'tree'])->name('menus.tree');
            Route::apiResource('menus', MenuAdminController::class)->only([
                'index', 'store', 'show', 'update', 'destroy',
            ]);

            // 用户管理路由 - 可通过配置禁用
            $disabledRoutes = config('iam.disabled_routes', []);
            if (!in_array('users', $disabledRoutes)) {
                Route::apiResource('users', UserController::class);
            }

            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class);

            // 部门管理
            Route::get('departments/tree', [DepartmentController::class, 'tree'])->name('departments.tree');
            Route::post('departments/{department}/move', [DepartmentController::class, 'move'])->name('departments.move');
            Route::apiResource('departments', DepartmentController::class);

            // 登录日志
            Route::get('login-logs/my', [LoginLogController::class, 'myLogs'])->name('login-logs.my');
            Route::apiResource('login-logs', LoginLogController::class)->only(['index', 'show']);
        });
    });

