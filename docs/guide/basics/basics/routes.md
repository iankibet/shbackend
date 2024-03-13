# Routing

App api routes to be placed at routes/api

Sh Framework system will automatically load them.
All routes will be relative to the file name

e.g for this file

```apis/tasks/tasks.route.php``` or ```apis/tasks/index.route.php```

Will result to this route

``` apis/tasks ``` route

naming file name same as file folder name, or naming it index.php will exclude
the file name from route path
