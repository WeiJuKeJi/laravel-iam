<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:150',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'username' => [
                'required',
                'string',
                'max:60',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'integer', 'exists:' . ConfigHelper::table('departments') . ',id'],
            'metadata' => ['nullable', 'array'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:' . ConfigHelper::table('roles') . ',id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '用户姓名',
            'email' => '邮箱',
            'username' => '用户名',
            'password' => '密码',
            'status' => '状态',
            'phone' => '联系电话',
            'department_id' => '所属部门',
            'metadata' => '扩展信息',
            'roles' => '角色集合',
        ];
    }
}
