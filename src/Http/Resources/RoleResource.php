<?php

namespace WeiJuKeJi\LaravelIam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'group' => $this->group,
            'guard_name' => $this->guard_name,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => PermissionResource::collection($this->permissions)->toArray($request),
                []
            ),
            'menus' => $this->whenLoaded('menus', fn () => $this->menus->map(fn ($m) => [
                'id' => $m->id, 'name' => $m->name, 'meta' => $m->meta,
            ])->toArray(), []),
            'menu_ids' => $this->whenLoaded('menus', fn () => $this->menus->pluck('id')->toArray(), []),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
