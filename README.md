# README.md
  - [Running the Application](#running-the-application)
  - [Routes](#routes)
    + [Get](#get)
    + [View](#view)

## Running the Application

Before continuing please ensure you have followed the [SETUP.md](SETUP.md) instructions.

---

## Routes
The route with no params is a simple index page.

[http://localhost:8000](http://localhost:8000)

The application supports both `get` and `view` commits, for any supported provider (currently only GitHub), and any 
repository within that provider.

---

### Get
The `get` route supports retrieving commits from the requested provider, repo owner and repo. The structure of the `get`
route is;

`http://localhost:8000/get/{provider}/{repo-owner}/{repo-name}`

| Parameter    | Required | Description                                                 |
|--------------|:--------:|-------------------------------------------------------------|
| `get`        |    *     | this is the route "action" and must be written as `get`.    |
| `provider`   |    *     | the only provider which is currently supported is `github`. |
| `repo-owner` |    *     | the *owner* of the repository you want to query.            |
| `repo-name`  |    *     | the *name* of the repository you want to query.             |

Following are some examples of valid `get` routes;

http://localhost:8000/get/github/nodejs/node

http://localhost:8000/get/github/Circunomics/hiring_backendcodechallenge

http://localhost:8000/get/github/falconsmilie/Raspberry-Pi-3-Weather

Incorrect or misspelt repository names are handled via the exception message being returned to the view. Malformed routes,
eg missing `repo-name` etc, will be redirected to the `index` route.

During development I was torn between putting this logic into a job which would run every minute (or as requirements dictate) 
on a background queue, or to keep it the way it is. It would depend on the end user for what is the better approach here.

---

### View
The `view` route is far more flexible, but is still formed in the same way as the `get` route. Currently, all `view` data 
is stored in the database and is the result of whatever `get` routes have previously been called. 

The structure of the `view` route is;

`http://localhost:8000/view/{provider}/{repo-owner}/{repo-name}`

| Parameter    | Required  | Description                                                 |
|--------------|:---------:|-------------------------------------------------------------|
| `view`       |     *     | this is the route "action" and must be written as `view`.   |
| `provider`   |     *     | the only provider which is currently supported is `github`. |
| `repo-owner` |           | the *owner* of the repository you want to query.            |
| `repo-name`  |           | the *name* of the repository you want to query.             |

Following are some example routes;

http://localhost:8000/view/github/nodejs/node (1)

http://localhost:8000/view/github/nodejs/ (2)

http://localhost:8000/view/github/ (3)

This route formatting allows us to view commits from, for example;
* the `node` repo (1)
* the `nodejs` owner (2)
* all `github` commits (3)

To understand how the commit data is stored, the database schema is available here; 
[2025_06_07_create_commits_table.php](source/database/migrations/2025_06_07_create_commits_table.php)