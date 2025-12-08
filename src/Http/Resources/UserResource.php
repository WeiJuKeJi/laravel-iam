<?php

namespace WeiJuKeJi\LaravelIam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'status' => $this->status,
            'phone' => $this->phone,
            'metadata' => $this->metadata,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'roles' => $this->whenLoaded(
                'roles',
                fn () => RoleResource::collection($this->roles)->toArray($request),
                []
            ),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => PermissionResource::collection($this->permissions)->toArray($request),
                []
            ),
        ];
    }
}
