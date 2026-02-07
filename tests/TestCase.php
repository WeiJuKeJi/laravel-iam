<?php

namespace WeiJuKeJi\LaravelIam\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WeiJuKeJi\LaravelIam\IamServiceProvider;

/**
 * Laravel IAM 测试基类
 */
abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // 运行迁移
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * 获取包的服务提供者
     */
    protected function getPackageProviders($app): array
    {
        return [
            IamServiceProvider::class,
            \Spatie\Permission\PermissionServiceProvider::class,
        ];
    }

    /**
     * 定义环境设置
     */
    protected function defineEnvironment($app): void
    {
        // 使用内存数据库进行测试
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // 设置认证配置
        $app['config']->set('auth.defaults.guard', 'sanctum');
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);

        // 设置缓存驱动
        $app['config']->set('cache.default', 'array');
    }
}
