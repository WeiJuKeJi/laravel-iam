<?php

namespace WeiJuKeJi\LaravelIam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', function () {
                return [
                    'id' => $this->manager->id,
                    'name' => $this->manager->name,
                    'username' => $this->manager->username,
                ];
            }),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'full_path' => $this->full_path,
            'level' => $this->level,
            'is_leaf' => $this->isLeaf(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'children' => $this->whenLoaded('children', function () {
                return DepartmentResource::collection($this->children);
            }),
            'ancestors' => $this->whenLoaded('ancestors', function () {
                return DepartmentResource::collection($this->ancestors);
            }),
            'descendants' => $this->whenLoaded('descendants', function () {
                return DepartmentResource::collection($this->descendants);
            }),
        ];
    }
}
