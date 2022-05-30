<?php

namespace Shara\Framework;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Shara\Framework\App\Commands\AutoGenerateModel;
use Shara\Framework\App\Commands\BackupDatabase;
use Shara\Framework\App\Commands\CreateSuperAdmin;
use Shara\Framework\App\Commands\Initialize;
use Shara\Framework\App\Commands\MakeApiEndPoint;
use Shara\Framework\App\Http\Middleware\ShAuth;


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
                CreateSuperAdmin::class
            ]);
        }
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('sh_auth', ShAuth::class);
        $this->loadRoutesFrom(__DIR__.'/routes/core.php');
    }
}
