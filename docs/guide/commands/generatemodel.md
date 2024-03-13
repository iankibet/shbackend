# sh:make-model

This is a command to autogenerate model and also 
associated migration

It helps easily create a model, define fields in the model and also
create migration fields

To autogenerate a model run this command

```shell
php artisan sh:make-model {model}
```

Make sure to replace {model} argunment with name of your model e.g

```shell
php artisan sh:make-model Task
```

You will then be prompted to choose model namespace, by default it's
Core, you can use the default one. 

Next step, you will be prompted to add migration fields

here, add field name and type separated by comma e.g

```shell
name, string
```

When done adding fields, just add N as field and it will terminate the loop

