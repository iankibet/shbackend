<?php

namespace Iankibet\Shbackend;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
<<<<<<< HEAD
use Shara\Framework\App\Commands\AutoGenerateModel;
use Shara\Framework\App\Commands\BackupDatabase;
use Shara\Framework\App\Commands\CreateSuperAdmin;
use Shara\Framework\App\Commands\Initialize;
use Shara\Framework\App\Commands\MakeApiEndPoint;
use Shara\Framework\App\Http\Middleware\ShAuth;
=======
use Iankibet\Shbackend\App\Commands\AutoGenerateModel;
use Iankibet\Shbackend\App\Commands\BackupDatabase;
use Iankibet\Shbackend\App\Commands\Initialize;
use Iankibet\Shbackend\App\Commands\MakeApiEndPoint;
use Iankibet\Shbackend\App\Http\Middleware\ShAuth;
>>>>>>> main


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
