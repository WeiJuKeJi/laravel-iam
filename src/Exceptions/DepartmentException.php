<?php

namespace WeiJuKeJi\LaravelIam\Exceptions;

/**
 * 部门相关异常基类
 */
class DepartmentException extends IamException
{
    /**
     * 部门不存在
     */
    public static function notFound(int $id): static
    {
        return static::make("部门不存在 (ID: {$id})", 404);
    }

    /**
     * 部门有子部门，无法删除
     */
    public static function hasChildren(): static
    {
        return static::make('请先删除子部门', 422);
    }

    /**
     * 部门有员工，无法删除
     */
    public static function hasUsers(): static
    {
        return static::make('该部门下还有员工，无法删除', 422);
    }
}
