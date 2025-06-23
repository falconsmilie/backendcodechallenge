# README.md

- [Implementation and Conceptual Questions](#implementation-and-conceptual-questions)
- [Running the Application](#running-the-application)
- [Routes](#routes)
    + [GET](#get)
    + [VIEW](#view)

## Implementation and Conceptual Questions

Can be found here [Q&A.md](Q&A.md).

## Running the Application

Can be setup from here [SETUP.md](SETUP.md).

## Routes

The root route without parameters returns an index page:

[http://localhost:8000](http://localhost:8000)

The application supports both `get` and `view` operations for commits, for any supported provider, and for any 
repository belonging to that provider.

---

### GET

The `get` route triggers retrieval of commits from the provider's repository owner and their repository. The format is:

```
http://localhost:8000/get/{provider}/{repo-owner}/{repo-name}
```

**Examples:**

- http://localhost:8000/get/github/nodejs/node
- http://localhost:8000/get/github/Circunomics/hiring_backendcodechallenge

| Route Parameters     |   Required   | Description                           |
|----------------------|:------------:|---------------------------------------|
| `get`                |      ✓       | Route action keyword; must be `get`.  |
| `provider`           |      ✓       | Currently only `github` is supported. |
| `repo-owner`         |      ✓       | Owner of the repository.              |
| `repo-name`          |      ✓       | Name of the repository.               |
| **Query Parameters** | **Required** | **Description**                       |
| `page`               |      ✕       | Which page of the results             |
| `results_per_page`   |      ✕       | How many results per page             |

Invalid or misspelled repository names will return an appropriate exception message. Malformed routes (eg missing 
parameters) redirect to the index route.

> [!NOTE]
> During development, i thought about implementing this as a scheduled job, rather than on-demand. I explain more in
> the code.

---

### VIEW

The `view` route retrieves commits stored in the database. It follows the same pattern as the `get` route:

```
http://localhost:8000/view/{provider}/{repo-owner}/{repo-name}
```

**Examples:**

- http://localhost:8000/view/github/nodejs/node
- http://localhost:8000/view/github/nodejs/
- http://localhost:8000/view/github/

This format enables flexible querying:

1. View commits for a specific repository.
2. View all commits from a specific repository owner.
3. View all GitHub commits stored in the database.

| Parameter     | Required | Description                           |
|---------------|:--------:|---------------------------------------|
| `view`        |    ✓     | Route action keyword; must be `view`. |
| `provider`    |    ✓     | Currently only `github` is supported. |
| `repo-owner`  |    ✕     | Repository owner (optional).          |
| `repo-name`   |    ✕     | Repository name (optional).           |
| **Query Parameters** | **Required** | **Description**                       |
| `commit_count`               |      ✕       | How many commits to get               |

To understand the structure of the stored data, refer to the database schema:

[2025_06_07_create_commits_table.php](source/database/migrations/2025_06_07_create_commits_table.php)
