<?php

namespace WeiJuKeJi\LaravelIam\ModelFilters;

use EloquentFilter\ModelFilter;

class DepartmentFilter extends ModelFilter
{
    /**
     * 按部门名称搜索
     */
    public function name($value): DepartmentFilter
    {
        return $this->where('name', 'like', "%{$value}%");
    }

    /**
     * 按部门编码搜索
     */
    public function code($value): DepartmentFilter
    {
        return $this->where('code', 'like', "%{$value}%");
    }

    /**
     * 按状态筛选
     */
    public function status($value): DepartmentFilter
    {
        return $this->where('status', $value);
    }

    /**
     * 按负责人筛选
     */
    public function managerId($value): DepartmentFilter
    {
        return $this->where('manager_id', $value);
    }

    /**
     * 按父部门筛选
     */
    public function parentId($value): DepartmentFilter
    {
        if ($value === 'null' || $value === null) {
            return $this->whereNull('parent_id');
        }

        return $this->where('parent_id', $value);
    }

    /**
     * 搜索（综合搜索）
     */
    public function search($value): DepartmentFilter
    {
        return $this->where(function ($query) use ($value) {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->orWhere('description', 'like', "%{$value}%");
        });
    }
}
