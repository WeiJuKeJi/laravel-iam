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
        $baseRules = [
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
            'user_type' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('iam.user_types', []))),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'integer', 'exists:' . ConfigHelper::table('departments') . ',id'],
            'metadata' => ['nullable', 'array'],
            'roles' => ['nullable', 'array'],
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

    /**
     * 准备验证数据
     * 如果未指定 user_type，使用配置的默认值
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('user_type')) {
            $this->merge([
                'user_type' => config('iam.default_user_type', 'default'),
            ]);
        }
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
