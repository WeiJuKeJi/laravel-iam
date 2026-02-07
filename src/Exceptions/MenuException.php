<?php

namespace WeiJuKeJi\LaravelIam\Exceptions;

/**
 * 菜单相关异常
 */
class MenuException extends IamException
{
    /**
     * 菜单不存在
     */
    public static function notFound(int $id): static
    {
        return static::make("菜单不存在 (ID: {$id})", 404);
    }

    /**
     * 菜单有子菜单，无法删除
     */
    public static function hasChildren(): static
    {
        return static::make('请先删除子菜单', 422);
    }

    /**
     * 菜单配置文件格式错误
     */
    public static function invalidConfig(string $reason): static
    {
        return static::make("菜单配置文件格式错误：{$reason}", 500);
    }

    /**
     * 菜单配置文件不存在
     */
    public static function configNotFound(string $path): static
    {
        return static::make("菜单配置文件不存在：{$path}", 404);
    }
}
