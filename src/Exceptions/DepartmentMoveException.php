<?php

namespace WeiJuKeJi\LaravelIam\Exceptions;

/**
 * 部门移动异常
 */
class DepartmentMoveException extends DepartmentException
{
    /**
     * 目标部门不存在
     */
    public static function targetNotFound(int $targetId): static
    {
        return static::make("目标部门不存在 (ID: {$targetId})", 404);
    }

    /**
     * 父部门不存在
     */
    public static function parentNotFound(int $parentId): static
    {
        return static::make("父部门不存在 (ID: {$parentId})", 404);
    }

    /**
     * 无效的移动操作
     */
    public static function invalidMove(string $reason): static
    {
        return static::make("部门移动失败：{$reason}", 422);
    }

    /**
     * 不能移动到自身
     */
    public static function cannotMoveToSelf(): static
    {
        return static::make('不能将部门移动到自身', 422);
    }

    /**
     * 不能移动到子部门
     */
    public static function cannotMoveToDescendant(): static
    {
        return static::make('不能将部门移动到其子部门下', 422);
    }
}
