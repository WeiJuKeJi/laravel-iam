<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class DepartmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:' . ConfigHelper::table('departments') . ',id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique(ConfigHelper::table('departments'), 'code')],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '父级部门',
            'name' => '部门名称',
            'code' => '部门编码',
            'manager_id' => '部门负责人',
            'sort_order' => '排序',
            'status' => '状态',
            'description' => '描述',
            'metadata' => '扩展数据',
        ];
    }
}
