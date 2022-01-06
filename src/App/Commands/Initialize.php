<?php

namespace Shara\Framework\App\Commands;

use Illuminate\Console\Command;

class Initialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shara:init';

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
        if(!file_exists($app_dir.'/Models/Core')){
            $command2 = 'cp -r '.__DIR__.'/../Models/Core '.$app_dir.'/Models/';
            exec($command2);
            $this->info("Copied default models");
        }
        if(!file_exists(!file_exists($controllers_dir.'/Api/Auth/AuthController.php'))) {
            $fileContents = file_get_contents(__DIR__.'/../../templates/AuthController.txt');
            file_put_contents($controllers_dir.'/Api/Auth/AuthController.php',$fileContents);
            $this->info("Created auth controller");
        }
        return Command::SUCCESS;
    }
}
