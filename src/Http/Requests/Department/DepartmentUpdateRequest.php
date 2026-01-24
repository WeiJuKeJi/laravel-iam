<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class DepartmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;
        $departments = ConfigHelper::table('departments');

        $parentRules = ['nullable', 'integer', 'exists:' . $departments . ',id'];

        // 不能将父部门设置为自己
        if ($departmentId) {
            $parentRules[] = Rule::notIn([$departmentId]);
        }

        return [
            'parent_id' => $parentRules,
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique($departments, 'code')->ignore($departmentId)],
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
