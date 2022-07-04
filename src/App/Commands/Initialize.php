<?php

namespace Iankibet\Shbackend\App\Commands;

use Illuminate\Console\Command;

class Initialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->warn('Creating routes api directory');
        $routes_dir  = app_path().'-';
        $routes_dir = str_replace('/app-','/routes/api',$routes_dir);
        if(!file_exists($routes_dir)) {
            mkdir($routes_dir);
            $copyCommand = 'cp -r '.__DIR__.'/../Routes/* '.$routes_dir.'/';
            exec($copyCommand);
            $this->info("success");
        } else {
            $this->info("Exists already!");
        }
        $this->warn("Copying models and controllers");
        $controllers_dir = app_path('Http/Controllers');
        $app_dir = app_path();
        if(!file_exists($controllers_dir.'/Api')){
            mkdir($controllers_dir.'/Api');
            $command = 'cp -r '.__DIR__.'/../Http/Controllers/* '.$controllers_dir.'/Api/';
            exec($command);
            $this->info('Created api controller directory');
        }
        if(!file_exists($app_dir.'/Models/Core/Department.php')){
            $command2 = 'cp -r '.__DIR__.'/../Models/Core '.$app_dir.'/Models/';
            exec($command2);
            $this->info("Copied default models");
        }
        $migrations_dir = str_replace('/app','/',$app_dir).'database/migrations';
        if(!file_exists($migrations_dir.'/2020_06_06_012829_create_log_types_table.php')){
            $command = 'cp -r '.__DIR__.'/../Migrations/* '.$migrations_dir.'/';
            exec($command);
            $this->info("Copied default migrations");
        } else {
            $this->warn('Migrations exist already');
        }
        if(!file_exists(storage_path('app/permissions/modules/common.json'))){
            $command = 'cp -r '.__DIR__.'/../permissions '.storage_path('app').'/';
            exec($command);
            $this->info("Copied default permissions");
        } else {
            $this->warn('Permissions exist already');
        }
//        $repositories_dir = str_replace('/app','/',$app_dir).'app/Repositories';
//        if(!file_exists($repositories_dir.'/helperrepo.php')){
//            if(!file_exists($repositories_dir)){
//                exec('mkdir '.$repositories_dir);
//            }
//            $command = 'cp -r '.__DIR__.'/../Repositories/* '.$repositories_dir.'/';
//            exec($command);
//            $this->info("Copied default repositories");
//        } else {
//            $this->warn('Repositories exist already');
//        }
        return Command::SUCCESS;
    }
}
