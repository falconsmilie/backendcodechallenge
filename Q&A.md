# Implementation and Conceptual Questions

- [How were you debugging this mini-project?](#how-were-you-debugging-this-mini-project)
- [Please give a detailed answer on your approach to test this project](#please-give-a-detailed-answer-on-your-approach-to-test-this-project)
- [Imagine this mini-project needs microservices with one single database](#imagine-this-mini-project-needs-microservices-with-one-single-database)
    * [Assumptions](#assumptions)
    * [Suggested Microservices](#suggested-microservices)
    * [Database Design](#database-design)
    * [Communication Between Services](#communication-between-services)
    * [Deployment Layout](#deployment-layout)
    * [Technologies](#technologies)
    * [Considerations](#considerations)
- [How would your solution differ if you had to call another external API to store and receive the commits](#how-would-your-solution-differ-if-you-had-to-call-another-external-api-to-store-and-receive-the-commits)
    * [The Core Shift](#the-core-shift)
    * [What Would Change](#what-would-change)
        + [1. Replace `saveCommits()`](#1-replace-savecommits)
        + [2. Update `getCommits()` and `countCommits()`](#2-update-getcommits-and-countcommits)
        + [3. Leave the Controller and Service Layer Alone](#3-leave-the-controller-and-service-layer-alone)
    * [Summary of What Changes](#summary-of-what-changes)
    * [Other Potential Improvements](#other-potential-improvements)

## How were you debugging this mini-project?
For the most part i was simply using `var_dump`. In a dedicated working environment I would prefer to use PhpStorm IDE
with XDebug.

## Please give a detailed answer on your approach to test this project
Due to time constraints, I focused exclusively on unit testing for this feature. Given the application’s reliance on 
external APIs and database interactions, isolating these dependencies was essential. I achieved this using mocks to 
ensure test determinism and avoid side effects. While I considered using Mockery for more expressive mocking, I opted 
to stay within PHPUnit’s native mocking capabilities to minimize dependency overhead. My approach wasn't driven by TDD, 
but rather by pragmatic unit testing aimed at verifying class-level logic and ensuring behavioural correctness in isolation.


## Imagine this mini-project needs microservices with one single database

Designing a **microservice-based architecture** for the version control integration mini-project, with a **single 
shared database**, requires careful consideration. Microservices ideally should be **loosely coupled and independently 
deployable**, but sharing a database introduces coupling. Still, it’s a common transitional setup or simplification in 
smaller projects.

---

###  Assumptions

* We want to scale horizontally in the future.
* We want to keep services separate for testing/deployment purposes.
* We have one database for now, but possibly modularized.
* The system fetches commit data from GitHub and similar services, stores it, and renders reports/UI.

---

### Suggested Microservices

1. **VersionControlFetcherService**

    * Fetches commits via GitHub/GitLab APIs.
    * Runs periodically or on-demand.
    * Writes commits to the database.
    * Queue-supported for rate-limiting and retries.

2. **CommitStorageService**

    * Abstracts commit persistence.
    * Handles deduplication and batch inserts.
    * Owns the `commits` table schema.
    * Exposes an internal API for write access.

3. **CommitQueryService**

    * Exposes endpoints for reading/stats.
    * Optimized for read patterns (caching, pagination).
    * No write access to DB.

4. **WebGateway/UIService**

    * Handles web routes (`/view`, `/get`, `/index`).
    * Talks to `CommitQueryService` and optionally `VersionControlFetcherService`.
    * Lightweight logic; presentation-focused.

5. **ErrorLogging/MonitoringService**

    * Receives logs/events/errors from other services.

---

### Database Design

One **PostgreSQL** or **MySQL** instance with at least the following tables:

* `commits` (central table)
* `services_log` (for debug/errors)
* Materialized views for reporting

> Each service **accesses the DB through its own repository layer** (no direct cross-service SQL).

---

### Communication Between Services

* Use **REST** or **gRPC** for internal service-to-service calls.
* Use **message queues** (RabbitMQ / Redis Streams / Kafka, Laravel Queues, Symfony Messenger) for async fetching, commit ingestion, or retries.

---

### Deployment Layout

```plaintext
/services
  /fetcher             # fetch commits
  /storage             # handles inserts
  /query               # read-only APIs
  /web-gateway         # UI + routes
```

> Use **Docker containers** per service. Define their own `Dockerfile`, `composer.json`, and tests.

---

### Technologies

| Component              | Technology                         |
| ---------------------- | ---------------------------------- |
| Language               | PHP 8.x                            |
| Framework              | Slim / Laravel Lumen (lightweight) |
| Queue (optional)       | Redis Queue / RabbitMQ             |
| API Gateway (optional) | NGINX reverse proxy                |
| Tests                  | PHPUnit + Docker CI                |
| Database               | MySQL or PostgreSQL                |
| Cache                  | Redis (optional)                   |

---

### Considerations

* **Avoid tight DB coupling:** If the database is shared, **encapsulate** all SQL access within one service per table.
* Define **clear service contracts**.
* Add **health checks** and **logging** per service.
* Use **OpenAPI / Swagger** to document service endpoints.

---

## How would your solution differ if you had to call another external API to store and receive the commits?
It would not vary much at all. The logic for storing and retrieving is abstracted into its own service connectors. The 
public API into the system would not change, therefore ensuring backwards compatibility.

Based on the current architecture, switching from "saving to a database" to "pushing to and fetching from an external API" 
instead, would only affect a few small areas.

---

### The Core Shift

Currently:

* `GitHubConnector::get()` fetches commits from GitHub and calls `saveCommits()` to insert into the **database** (via `Commit::insertOrIgnore`).
* The controller (`VersionHistoryController`) shows the view using `VersionControlFactory → GitHubService → GitHubConnector`.

We now need to:

* Send commits to an external API (instead of saving to DB).
* Fetch commits from that API (instead of querying your own DB).

---

### What Would Change

#### 1. Replace `saveCommits()`

Replace:

```php
protected function saveCommits(array $commits): void
{
    collect($commits)
        ->chunk(500)
        ->each(fn($chunk) => Commit::insertOrIgnore($chunk->toArray()));
}
```

With something like:

```php
protected function saveCommits(array $commits): void
{
    $response = $this->client->post('https://external-api.example.com/commits', [
        'json' => $commits,
    ]);

    if ($response->getStatusCode() !== 200) {
        throw new VersionControlException('Failed to store commits externally.');
    }
}
```

We’re now pushing commits **via HTTP** instead of writing them to a DB.

---

#### 2. Update `getCommits()` and `countCommits()`

These are currently querying the local database:

```php
public function getCommits(int $page, int $resultsPerPage): Collection
{
    return Commit::where(...)->get()->groupBy('author');
}

public function countCommits(): int
{
    return Commit::count();
}
```

We would now make **external API GET requests**:

```php
public function getCommits(int $page, int $resultsPerPage): Collection
{
    $response = $this->client->get("https://external-api.example.com/commits", [
        'query' => [
            'owner' => $this->owner,
            'repo' => $this->repo,
            'page' => $page,
            'per_page' => $resultsPerPage,
        ],
    ]);

    $data = json_decode($response->getBody()->getContents(), true);

    return collect($data)->groupBy('author');
}

public function countCommits(): int
{
    $response = $this->client->get("https://external-api.example.com/commits/count", [
        'query' => [
            'owner' => $this->owner,
            'repo' => $this->repo,
        ],
    ]);

    $data = json_decode($response->getBody()->getContents(), true);

    return $data['count'] ?? 0;
}
```

---

#### 3. Leave the Controller and Service Layer Alone

The `VersionHistoryController` and `VersionControlFactory` don’t need to change at all.

```php
new VersionControlFactory($this->provider, $this->owner, $this->repo)
    ->make()
    ->get();
```

Because the **connector is encapsulated**, and there is a well-defined **separation of concerns**, we’re simply swapping DB 
logic for HTTP logic.

---

### Summary of What Changes

| Layer                               | Current              | New                                 |
| ----------------------------------- | -------------------- | ----------------------------------- |
| **Controller**                      | No changes           |                                     |
| **AbstractVersionControlService**   | No changes           |                                     |
| **GitHubConnector::saveCommits()**  | Eloquent bulk insert | POST to external API                |
| **GitHubConnector::getCommits()**   | DB read + groupBy    | GET from external API               |
| **GitHubConnector::countCommits()** | DB count             | GET from external API               |
| **Testing**                         | Mock DB              | Mock external API (via Guzzle client) |

---

### Other Potential Improvements

* If the external API supports **batching**, respect rate limits using retries or Guzzle’s `handler stack`.
* Consider caching external results to reduce API calls.
