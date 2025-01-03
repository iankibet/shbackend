<?php

namespace Iankibet\Shbackend\App\Commands;

use Illuminate\Console\Command;
use Artisan;
use Illuminate\Support\Facades\Storage;
use File;
use Illuminate\Support\Str;

class AutoGenerateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:make-model {model_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a model, migration, controller, route and view at the same time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $real_model;
    protected $real_controller;
    protected $model_name;
    protected $fields;
    protected $plain_fields;
    protected $model_fields;
    protected $route_url;
    protected $controller_name;
    protected $controller_class;
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model_name = $this->argument('model_name');
        $this->model_name = $model_name;
        $model_namespace = $this->ask("What is the model namespace?","Core");
        if(!$model_namespace){
            $model_namespace = "Core";
        }
        $real_model = $model_namespace."/".$model_name;
        $this->real_model = $real_model;
        $plain_fields = [];
        $fields = [];
        $add_more = 1;

        while($add_more){
            $field_name = $this->ask("Add migration field(Name,Type[string]), N to end");
            if(strtolower($field_name) == 'n'){
                $add_more = 0;
            }else{
                $default_field_type = 'string';
                $field_type = @explode(',',$field_name)[1];
                if(!$field_type){
                    $field_type = $this->getFieldType($field_name);
                }
                $field_type = str_replace('datetime','dateTime',$field_type);
                $field_type = str_replace('longtext','longText',$field_type);
                $field_name = @explode(',',$field_name)[0];
                $fields[] = [
                    'name'=>$field_name,
                    'type'=>$field_type
                ];
                $plain_fields[] = $field_name;
            }
        }
        $this->fields = $fields;
        $this->plain_fields = $plain_fields;
        $this->createModel();
        $this->updateMigrationFields();
        $this->info("Done!");
    }

    public function createModel(){
        Artisan::call("make:model",[
            'name'=>$this->real_model,
            '-m'=>true
        ]);
        $model_path = app_path("Models/".$this->real_model.'.php');
        $model_content = file_get_contents($model_path);
        $model_array = explode('//',$model_content);
        $pre_model_content = $model_array[0];
        $post_model_content = $model_array[1];
        // removed use factoru
        $this->model_fields = '"'.implode('","',$this->plain_fields).'"';
        $current_model_content = "\n\t".'protected $fillable = ['.$this->model_fields.'];'."\n";
        $new_model_contents = $pre_model_content.$current_model_content.$post_model_content;
        file_put_contents($model_path,$new_model_contents);
    }

    protected function updateMigrationFields(){
        $migration_dir = base_path('database/migrations');
        $migrations = scandir($migration_dir);
        $migration = $migration_dir.'/'.$migrations[count($migrations)-1];
        $migration_contents = file_get_contents($migration);
        $migration_arr = explode('$table->id();'."\n",$migration_contents);
        $pre_migration_content = $migration_arr[0];
        $after_migration_content = $migration_arr[1];
        $current_migration_content = '$table->id();'."\n";
        $fields = $this->fields;
        $nullables = ['description','bio'];
        $defaults = [
            'status'=>0,
        ];
        foreach($fields as $field){
            $current_migration_content.="\t\t\t".'$table->'.$field['type'].'(\''.$field['name'].'\')';
            if(in_array($field['name'],$nullables))
                $current_migration_content.='->nullable()';

            if(isset($defaults[$field['name']]))
                $current_migration_content.='->default('.$defaults[$field['name']].')';

            $current_migration_content.=';'."\n";
        }
        $new_migration_content = $pre_migration_content.$current_migration_content.$after_migration_content;
        file_put_contents($migration,$new_migration_content);
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
        $new_content = str_replace('{model_fields}',$this->model_fields,$new_content);
        $new_content = str_replace('{model_namespace}',str_replace('/','\\',$this->real_model),$new_content);
        return $new_content;
    }
    public function getFieldType($field_name){
        $textareas = ['description','answer','more_information','reason','email_message','sms_message','html',
            'comment',"testimonial",'about','address','postal_address','message','invoice_footer',
            'security_credential','reason_rejected','note','instructions'];
        if(in_array($field_name,$textareas))
            return 'longText';
        $arr = explode('_',$field_name);
        if($arr[count($arr)-1] == 'id')
            return 'integer';
        if($arr[count($arr)-1] == 'at')
            return 'dateTime';
        if($arr[0] == 'date')
            return 'dateTime';
        $doubles = ['age','year','height','width','amount','price','discount','deposit','rate','percentage','year_of_birth','id_number'];
        if(in_array($field_name,$doubles))
            return 'double';
        return 'string';

    }
}
