<?php

namespace WeiJuKeJi\LaravelIam\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'path' => $this->path,
            'component' => $this->component,
            'redirect' => $this->redirect,
            'sort_order' => $this->sort_order,
            'is_enabled' => $this->is_enabled,
            'meta' => $this->meta ?? [],
            'guard' => $this->guard ?? [],
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name'), []),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name'), []),
            'children' => $this->whenLoaded(
                'children',
                fn () => MenuResource::collection($this->children)->toArray($request),
                []
            ),
        ];
    }
}
