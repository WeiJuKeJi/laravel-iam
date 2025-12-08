<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WeiJuKeJi\LaravelIam\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $forceRefresh = $request->boolean('refresh') || $request->boolean('force') || $request->boolean('invalidate');

        $payload = $this->menuService->getMenuTreeForUser($request->user(), $forceRefresh);

        return $this->success($payload)->header('X-Menu-Version', $payload['version'] ?? '');
    }
}
