<?php

namespace Iankibet\Shbackend\App\Commands;

use App\Models\Core\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeApiEndPoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:make-endpoint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create api route and controller';

    protected $real_model;
    protected $real_controller;
    protected $model_name;
    protected $fields;
    protected $route_url;
    protected $controller_name;
    protected $controller_class;
    protected $controllerTemplatePath;
    protected $routeTemplatePath;

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
        if(file_exists(storage_path('app/shara-framework/templates/api/controller.txt'))){
            $this->controllerTemplatePath = storage_path('app/shara-framework/templates/controller.txt');
        } else {
            $this->controllerTemplatePath = __DIR__.'/../../templates/api/controller.txt';
        }

        if(file_exists(storage_path('app/shara-framework/templates/api/controller.txt'))){
            $this->routeTemplatePath = storage_path('app/shara-framework/templates/route.txt');
        } else {
            $this->routeTemplatePath = __DIR__.'/../../templates/api/route.txt';
        }
        $model_name = $this->ask("What is the name of the model?");
        $this->model_name = $model_name;
        $model_namespace = $this->ask("What is the model namespace?","Core");
        if(!$model_namespace){
            $model_namespace = "Core";
        }
        $real_model = $model_namespace."/".$model_name;
        $model_path = app_path("Models/".$real_model.'.php');
        if(!file_exists($model_path)){
            $this->error("Model ".$real_model." does not exist!");
            return 0;
        }
        $this->real_model = $real_model;
        $default_controller_name = Str::plural($model_name).'Controller';
        $controller_name = $this->ask("What is the controller name?",$default_controller_name);
        $this->controller_name = $controller_name;
        $controller_namespace = $this->ask("What is the controller namespace (use / to separate folders)?");
        $controller_namespace = ucfirst($controller_namespace);
        $this->route_url = strtolower($controller_namespace);
        $this->real_controller = $controller_namespace.'/'.$controller_name;
        $controller_class = "\App\Http\Controllers\Api\\".str_replace('/','\\',$this->real_controller).'::class';
        $this->controller_class = $controller_class;
        $this->createRoute();
        $this->createController();
        $this->info("Api Routes and Controllers created successfully");
        $generated_outputs = [];
        $generated_outputs[] = [
          'method'=>'POST',
          'url'=> '/api/'.$this->route_url.'/store',
            'action'=> 'Store/Update '.$model_name
        ];
        $generated_outputs[] = [
          'method'=>'GET',
          'url'=> '/api/'.$this->route_url.'/list/any',
            'action'=> 'List any '.strtolower(Str::plural($model_name))
        ];
        $generated_outputs[] = [
          'method'=>'GET',
          'url'=> '/api/'.$this->route_url.'/list/self',
            'action'=> 'List self '.strtolower(Str::plural($model_name))
        ];
        $generated_outputs[] = [
          'method'=>'GET',
          'url'=> '/api/'.$this->route_url.'/'.strtolower($model_name).'/get/self/{id}',
            'action'=> 'Get one '.strtolower($model_name).' belonging to user'
        ];
        $generated_outputs[] = [
          'method'=>'GET',
          'url'=> '/api/'.$this->route_url.'/'.strtolower($model_name).'/get/any/{id}',
            'action'=> 'Get one for any user '.strtolower($model_name)
        ];
        $this->table(['method','url','action'],$generated_outputs);
        $this->info("Happy SH Backend development :)");
        return 0;
    }

    public function createController(){
        Artisan::call("make:controller",[
            'name'=>'Api/'.$this->real_controller
        ]);
        $path = app_path("Http/Controllers/Api/".$this->real_controller.'.php');
        $content = file_get_contents($path);

        $new_content = file_get_contents($this->controllerTemplatePath);
        $new_content = $this->replaceVars($new_content);
        $new_controller = str_replace('//',$new_content,$content);
        $use_content = 'use App\\Models\\'.str_replace('/','\\',$this->real_model).";\nuse Iankibet\Shbackend\App\Repositories\SearchRepo;\nuse Illuminate\Support\Facades\Validator;\nuse Illuminate\Support\Facades\Schema;\n\nclass";
        $new_controller = $this->replace_first('class',$use_content,$new_controller);
        file_put_contents($path,$new_controller);
    }

    public function replaceVars($content){
        $model = strtolower($this->model_name);
        $umodel = $this->model_name;
        $models = Str::plural($model);
        $umodels = Str::plural($umodel);
        $new_content = str_replace('{model}',$model,$content);
        $new_content = str_replace('{cmodel}',strtoupper($model),$new_content);
        $new_content = str_replace('{models}',$models,$new_content);
        $new_content = str_replace('{umodel}',$umodel,$new_content);
        $new_content = str_replace('{umodels}',$umodels,$new_content);
        $new_content = str_replace('{route_url}',$this->route_url,$new_content);
        $new_content = str_replace('{controller}',$this->controller_name,$new_content);
        $new_content = str_replace('{controller_class}',$this->controller_class,$new_content);
        $new_content = str_replace('{model_namespace}',str_replace('/','\\',$this->real_model),$new_content);
        return $new_content;
    }

    public function createRoute(){
        $route_file = strtolower($this->route_url.'/'.Str::plural($this->model_name)).'.route.php';
        $content = file_get_contents($this->routeTemplatePath);
        $new_content = $this->replaceVars($content);
        $route_path = base_path("routes/api/".$route_file);
        $this->storeFile($route_path,$new_content);
    }

    public function storeFile($original_path,$contents){
        $original_path = str_replace('\\','/',$original_path);
        $path_arr = explode("/",$original_path);
        unset($path_arr[count($path_arr)-1]);
        $dir = implode("/",$path_arr);
        exec("mkdir -p $dir");
        file_put_contents($original_path,$contents);
    }

    public function replace_first($find, $replace, $subject) {
        return implode($replace, explode($find, $subject, 2));
    }
}
