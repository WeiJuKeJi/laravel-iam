<?php

use Illuminate\Support\Facades\Route;
use WeiJuKeJi\LaravelIam\Http\Controllers\AuthController;
use WeiJuKeJi\LaravelIam\Http\Controllers\MenuController;
use WeiJuKeJi\LaravelIam\Http\Controllers\MenuAdminController;
use WeiJuKeJi\LaravelIam\Http\Controllers\PermissionController;
use WeiJuKeJi\LaravelIam\Http\Controllers\RoleController;
use WeiJuKeJi\LaravelIam\Http\Controllers\UserController;

Route::middleware(['api'])
    ->prefix('v1/iam')
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

            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class);
        });
    });
