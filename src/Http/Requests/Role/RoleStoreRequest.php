<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guard = $this->input('guard_name', 'sanctum');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique(ConfigHelper::table('roles'), 'name')->where(fn ($query) => $query->where('guard_name', $guard)),
            ],
            'display_name' => ['nullable', 'string', 'max:120'],
            'group' => ['nullable', 'string', 'max:120'],
            'guard_name' => ['nullable', 'string', 'max:60'],
            'metadata' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:' . ConfigHelper::table('permissions') . ',id'],
            'menu_ids' => ['nullable', 'array'],
            'menu_ids.*' => ['integer', 'exists:' . ConfigHelper::table('menus') . ',id'],
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
            'menu_ids' => '菜单集合',
        ];
    }
}
