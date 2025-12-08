<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Models\Permission;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Permission|null $permission */
        $permission = $this->route('permission');
        $permissionId = $permission?->getKey();
        $guard = $this->input('guard_name', $permission?->guard_name ?? 'sanctum');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('permissions', 'name')->where(fn ($query) => $query->where('guard_name', $guard))->ignore($permissionId),
            ],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'group' => ['sometimes', 'nullable', 'string', 'max:120'],
            'guard_name' => ['sometimes', 'string', 'max:60'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '权限名称',
            'display_name' => '显示名称',
            'group' => '权限分组',
            'guard_name' => '守卫',
        ];
    }
}
