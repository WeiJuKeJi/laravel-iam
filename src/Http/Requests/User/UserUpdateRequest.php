<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

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

        $baseRules = [
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
            'user_type' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(array_keys(config('iam.user_types', []))),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:' . ConfigHelper::table('departments') . ',id'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:' . ConfigHelper::table('roles') . ',id'],
        ];

        // 允许应用层添加自定义字段验证
        return array_merge($baseRules, $this->customRules());
    }

    /**
     * 自定义验证规则（供子类重写）
     *
     * @return array
     */
    protected function customRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'name' => '用户姓名',
            'email' => '邮箱',
            'username' => '用户名',
            'password' => '密码',
            'status' => '状态',
            'user_type' => '用户类型',
            'phone' => '联系电话',
            'department_id' => '所属部门',
            'metadata' => '扩展信息',
            'roles' => '角色集合',
        ];
    }
}
