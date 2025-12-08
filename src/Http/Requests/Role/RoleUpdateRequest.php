<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Models\Role;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');
        $roleId = $role?->getKey();
        $guard = $this->input('guard_name', $role?->guard_name ?? 'sanctum');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:120',
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', $guard))->ignore($roleId),
            ],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'group' => ['sometimes', 'nullable', 'string', 'max:120'],
            'guard_name' => ['sometimes', 'string', 'max:60'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '角色名称',
            'display_name' => '显示名称',
            'group' => '角色分组',
            'guard_name' => '守卫',
            'permissions' => '权限集合',
        ];
    }
}
