<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class MenuStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_ids' => $this->input('role_ids', []),
            'permission_ids' => $this->input('permission_ids', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:' . ConfigHelper::table('menus') . ',id'],
            'name' => ['required', 'string', 'max:64', Rule::unique(ConfigHelper::table('menus'), 'name')],
            'path' => ['required', 'string', 'max:255'],
            'component' => ['nullable', 'string', 'max:255'],
            'redirect' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'is_enabled' => ['sometimes', 'boolean'],
            'meta' => ['nullable', 'array'],
            'guard' => ['nullable', 'array'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:' . ConfigHelper::table('roles') . ',id'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:' . ConfigHelper::table('permissions') . ',id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '父级菜单',
            'name' => '路由名称',
            'path' => '路由路径',
            'component' => '组件路径',
            'redirect' => '重定向地址',
            'sort_order' => '排序',
            'is_enabled' => '启用状态',
            'meta' => '路由元信息',
            'guard' => '守卫配置',
            'role_ids' => '关联角色',
            'permission_ids' => '关联权限',
        ];
    }
}

