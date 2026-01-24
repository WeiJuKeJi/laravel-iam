<?php

namespace WeiJuKeJi\LaravelIam\Support;

class ConfigHelper
{
    /**
     * 获取配置的表前缀
     */
    public static function getTablePrefix(): string
    {
        return config('iam.table_prefix', 'iam_');
    }

    /**
     * 获取完整的表名（带前缀）
     *
     * @param string $table 表名（不带前缀）
     * @return string 完整的表名
     */
    public static function table(string $table): string
    {
        return self::getTablePrefix() . $table;
    }

    /**
     * 获取所有 IAM 表名
     *
     * @return array<string, string>
     */
    public static function getTables(): array
    {
        $prefix = self::getTablePrefix();

        return [
            'permissions' => $prefix . 'permissions',
            'roles' => $prefix . 'roles',
            'model_has_permissions' => $prefix . 'model_has_permissions',
            'model_has_roles' => $prefix . 'model_has_roles',
            'role_has_permissions' => $prefix . 'role_has_permissions',
            'menus' => $prefix . 'menus',
            'menu_role' => $prefix . 'menu_role',
            'menu_permission' => $prefix . 'menu_permission',
            'departments' => $prefix . 'departments',
        ];
    }
}
