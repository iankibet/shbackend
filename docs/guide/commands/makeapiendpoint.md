# sh:make-endpoint

This helps create a resource end point

Let say for example you want to create resource endpoint for Task mode
This will create ```add```, ```edit```, ```delete``` and ```list``` tasks endpoints and 
controllers

To create resource end points, run the following command

```shell
php artisan sh:make-endpoint
```

It will then prompt you for the model

enter your model name e.g ```Task```

It will then request for the model namespace, usually we will have it
at ``Core`` you can go with the default

If the model is found, the system will automatically create for you the api end
points and show you all the created urls
