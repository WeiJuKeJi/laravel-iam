<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Models\User;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');
        $userId = $user?->getKey();

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => [
                'sometimes',
                'email:rfc,dns',
                'max:150',
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($userId),
            ],
            'username' => [
                'sometimes',
                'string',
                'max:60',
                Rule::unique('users', 'username')->whereNull('deleted_at')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive'])],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
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
            'metadata' => '扩展信息',
            'roles' => '角色集合',
        ];
    }
}
