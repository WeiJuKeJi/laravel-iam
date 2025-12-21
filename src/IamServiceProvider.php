<?php

namespace WeiJuKeJi\LaravelIam;

use Illuminate\Support\ServiceProvider;

class IamServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 加载路由
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // 加载迁移
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 加载视图
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'iam');

        // 发布配置文件
        $this->publishes([
            __DIR__.'/../config/iam.php' => config_path('iam.php'),
        ], 'iam-config');

        // 发布迁移文件
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'iam-migrations');

        // 发布 Seeders
        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders'),
        ], 'iam-seeders');

        // 发布视图
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/iam'),
        ], 'iam-views');

        // 注册 Artisan 命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallCommand::class,
                Console\Commands\ExportMenusCommand::class,
                Console\Commands\SyncPermissionsCommand::class,
                Console\Commands\UninstallCommand::class,
                Console\Commands\MenuReseedCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__.'/../config/iam.php',
            'iam'
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
