# Roles and Permissions

## Introduction

ShBackend roles and permisions work using middleware and urls.

All urls in the system are attached to a certain permission.

A url can be attached to multiple permissions.

All urls in the system must be added to a certain permission.

``ShAuth`` middleware validates all api requests under `api` folder in `routes/api/`.

For all the requests to api, `ShAuth` gets the url, finds it belongs to what 
permissions then checks if the user making the request
has that permission. It then returns a `403` not authorized if user
does not have that permission.

Urls are attached to permissions using json files called modules.

## Modules

Permission modules are `.json` files found in `storage/app/permissions` folder.

Below is an example of permission module file for ``tasks``.

File name and path
``storage/app/permissions/modules/tasks.json``

File contents
```json
{
  "main": "/tasks",
  "roles":["admin","member"],
  "urls": ["list"],
  "children": {
    "store_task": {
      "main": "store",
      "roles": ["admin"],
      "urls": ["/users/list"]
    }
  }
}
```

From the example above, we have tasks module with a child.

The modules will be split into 2 permissions (`tasks`, `tasks.store_task`) as shown below

Permisison 1:

`tasks`

User roles allowed to access the permission: ``admin,member``

Urls in the permission:

```
/tasks
/tasks/list
```

Permisison 2

`tasks.store_task`

User roles allowed to access the permission: ``admin``

Urls in the permission

```
/tasks/store
/users/list
```

From the above note: ``/tasks/store`` was generated from main, `store` which is the child of a parent with `/tasks` as the main url.

Any url that starts with `/` will start from the rool url. The others will be concantenated with url in `main`

e.g ``/users/list`` in the example above.

Also note that a url can belong to multiple permissions.

