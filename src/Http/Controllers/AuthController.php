<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WeiJuKeJi\LaravelIam\Http\Requests\Auth\LoginRequest;
use WeiJuKeJi\LaravelIam\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $payload = $this->authService->attemptLogin(
            $credentials['account'] ?? $credentials['username'],
            $credentials['password'],
            $request->ip(),
            $request->userAgent()
        );

        return $this->success($payload, '登录成功');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, '退出成功');
    }

    public function me(Request $request): JsonResponse
    {
        $profile = $this->authService->profile($request->user());

        return $this->success($profile);
    }
}
