# ShRepository

This is a helper class with methods that 
make it easy to call laravel core methods.


## Importing

```php
use Iankibet\Shbackend\App\Repositories\ShRepository;
```

## Static Methods

### ``storeLog``

Store log of an action done in the system. Data will be stored to logs table.

Example

```php
 ShRepository::storeLog('created_task',"Created a new task #$task->id $task->title",$task);
```

It takes a slug log type ``created_at``, Then the log itself, ``Created a new task #$task->id $task->title`` then the model of the item we are trying to store logs for ``$task``

### ``saveModel``

Save a model to database easily.

It takes 3 parameters, ``model``, ``fillable`` data, ``forceFill`` data

e.g

```php
$model = new Task();
$taskData = [
    'title'=>'Test task',
    'description'=>'Test description'
];
$task = ShRepository::saveModel($model,$data,['user_id'=>1])
```


### ``getFillables``

Get fillable fields from a model class as array

e.g

```php
$task = new Task();
$fillables = ShRepository::getFillables($task);
```

Above returns ``['name','description']``

### ``getValidationFields``

This returns validation rules for a model from it's fillable array.

e.g

```php
$task = new Task();
$rules =ShRepository::getValidationRules($task, ['user_id']);
//This returns
[
    'name'=>'required',
    'description'=>'required',
    'user_id'=>'required'
]
```



