<?php

namespace Iankibet\Shbackend;

use Iankibet\Shbackend\App\Commands\CacheData;
use Iankibet\Shbackend\App\Commands\CachePermissions;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use Iankibet\Shbackend\App\Commands\AutoGenerateModel;
use Iankibet\Shbackend\App\Commands\BackupDatabase;
use Iankibet\Shbackend\App\Commands\Initialize;
use Iankibet\Shbackend\App\Commands\MakeApiEndPoint;
use Iankibet\Shbackend\App\Http\Middleware\ShAuth;
use Iankibet\Shbackend\App\Commands\CreateSuperAdmin;



class SharaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoGenerateModel::class,
                BackupDatabase::class,
                MakeApiEndPoint::class,
                Initialize::class,
                CreateSuperAdmin::class,
                CachePermissions::class,
                CacheData::class
            ]);
        }
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('sh_auth', ShAuth::class);
        $this->loadRoutesFrom(__DIR__.'/routes/core.php');
        $this->loadRoutesFrom(__DIR__.'/routes/sh-ql.route.php');
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->publishes([
            __DIR__.'/config/shqlqueries.php' => config_path('shqlqueries.php'),
        ]);
        $this->publishes([
            __DIR__.'/config/shqlmutations.php' => config_path('shqlmutations.php'),
        ]);
        $this->publishes([
            __DIR__.'/config/shconfig.php' => config_path('shconfig.php'),
        ]);
    }
}
