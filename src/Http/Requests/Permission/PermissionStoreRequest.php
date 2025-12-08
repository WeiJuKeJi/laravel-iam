<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionStoreRequest extends FormRequest
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
                'max:150',
                Rule::unique('permissions', 'name')->where(fn ($query) => $query->where('guard_name', $guard)),
            ],
            'display_name' => ['nullable', 'string', 'max:150'],
            'group' => ['nullable', 'string', 'max:120'],
            'guard_name' => ['nullable', 'string', 'max:60'],
            'metadata' => ['nullable', 'array'],
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
