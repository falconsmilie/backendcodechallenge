# Implementation and Conceptual Questions

- [How were you debugging this mini-project?](#how-were-you-debugging-this-mini-project)
- [Please give a detailed answer on your approach to test this project](#please-give-a-detailed-answer-on-your-approach-to-test-this-project)
- [Imagine this mini-project needs microservices with one single database](#imagine-this-mini-project-needs-microservices-with-one-single-database)
    * [Suggested Microservices](#microservices)
    * [Database Design](#database)
    * [Communication Between Services](#communication-between-services)
    * [Deployment Layout](#deployment-layout)
    * [Considerations](#considerations)
- [How would your solution differ if you had to call another external API to store and receive the commits](#how-would-your-solution-differ-if-you-had-to-call-another-external-api-to-store-and-receive-the-commits)
    * [What Would Change](#what-would-change)
        + [1. Refactor `saveMany()`](#1-refactor-savemany)
        + [2. Refactor `getByProviderGroupedByAuthor()` and `countByProvider()`](#2-refactor-getbyprovidergroupedbyauthor-and-countbyprovider)
        + [3. Leave the Controller and Service Layer Alone](#3-leave-the-controller-and-service-layer-alone)
    * [Other Potential Improvements](#other-potential-improvements)

## How were you debugging this mini-project?
For the most part i was using `var_dump`. The unit tests were also handy because most of them were written early. In a 
dedicated working environment I would also use PhpStorm IDE with XDebug.

## Please give a detailed answer on your approach to test this project
Due to time constraints, I focused exclusively on Unit tests. Given the application’s reliance on external APIs and database 
interactions, isolating these dependencies was important. I considered using Mockery but opted 
to stay within PHPUnit’s mocking. 

## Imagine this mini-project needs microservices with one single database

Microservices should be **loosely coupled and independently deployable**. Sharing a database introduces 
coupling.

---

### Considerations

* **Avoid tight DB coupling:** If the database is shared, **encapsulate** all SQL access within one service per table.
* Define **clear service contracts**.
* Add **health checks** and **logging** per service.
* Use **OpenAPI / Swagger** to document service endpoints.

---

### Microservices

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

    * Exposes endpoints for reading.
    * Optimized for read patterns (caching, pagination).
    * No write access to DB.

4. **WebGateway/UIService**

    * Handles web routes (`/view`, `/get`, `/index`).
    * Talks to `CommitQueryService` and optionally `VersionControlFetcherService`.
    * Lightweight logic; presentation-focused.

5. **ErrorLogging/MonitoringService**

    * Receives logs/events/errors from other services.

---

### Database

One **PostgreSQL** or **MySQL** instance with at least the following tables:

* `commits` (central table)
* `services_log` (for debug/errors)
* If supported, materialized views for reporting

> Each service **accesses the DB through its own repository layer** (no direct cross-service SQL).

---

### Communication Between Services

* Use **REST** or **gRPC** for internal service-to-service calls.
* Use **message queues** (RabbitMQ / Redis Streams / Kafka) for async fetching, commit ingestion, or retries.

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

## How would your solution differ if you had to call another external API to store and receive the commits?
We may need to update the [GitHubApi](source/app/Api/GitHub/GitHubApi.php) depending on *where* the external API is. 
In any case though, we could adapt the existing [GitHubApiGetter](source/app/Services/GitHub/GitHubApiGetter.php) 
(or create a new class) to implement the `CommitViewInterface` and `CommitSaveInterface`. Then migrate and refactor the 
methods from `MySqlCommitRepository` to the `GitHubApiConnector` to make HTTP requests (from the API), instead of 
accessing the database.

---

### What Would Change

#### 1. Refactor `saveMany()`

Replace:

```php
public function saveMany(array $commits): void
{
    collect($commits)
        ->chunk(500)
        ->each(fn($chunk) => Commit::insertOrIgnore($chunk->toArray()));
}
```

With something like:

```php
public function saveMany(array $commits): void
{
    $response = $this->gitHubApi->post($commits);

    return formatForConsumer($response);
}
```

We’re now pushing commits **via HTTP** instead of writing them to a DB.

---

#### 2. Refactor `getByProviderGroupedByAuthor()` and `countByProvider()`

These are currently querying the local database:

```php
public function getByProviderGroupedByAuthor(int $page, int $resultsPerPage): array
{
    return Commit::where(...)->get()->groupBy('author');
}

public function countByProvider(): int
{
    return Commit::count();
}
```

We now make external API GET requests for both methods. For example:

```php
public function getByProviderGroupedByAuthor(int $page, int $resultsPerPage): array
{
    $response = $this->gitHubApi->get($this->owner, $this->repo, $page, $resultsPerPage);

    return formatForConsumer($response);
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

Because the **Repositories are encapsulated**, and there is a well-defined **separation of concerns**, we’re simply 
swapping database logic for HTTP logic.

---

### Other Potential Improvements

* If the external API supports **batching**, respect rate limits using retries or Guzzle’s `handler stack`.
* Consider caching external results to reduce API calls.
