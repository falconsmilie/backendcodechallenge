# Implementation and Conceptual Questions
- [Implementation and Conceptual Questions](#implementation-and-conceptual-questions)
    * [The Brief](#the-brief)
    * [How would your solution differ if you had to call another external API to store and receive the commits?](#how-would-your-solution-differ-if-you-had-to-call-another-external-api-to-store-and-receive-the-commits)
        + [Assumptions](#assumptions)
        + [`CommitRepository`](#commitrepository)
        + [Benefits](#benefits)
        + [Other Potential Improvements](#other-potential-improvements)
    * [Imagine this mini-project needs microservices with one single database](#imagine-this-mini-project-needs-microservices-with-one-single-database)
        + [Considerations](#considerations)
        + [Microservices](#microservices)
        + [Database](#database)
        + [Communication Between Services](#communication-between-services)
        + [Deployment Layout](#deployment-layout)

## The Brief
The task is outlined [here](https://github.com/Circunomics/hiring_backendcodechallenge).

---
## How would your solution differ if you had to call another external API to store and receive the commits?

### Assumptions

* There's an external HTTP API we call to store and retrieve commits.
* The external API accepts JSON arrays of commits (similar to `CommitDTO::toArray()`).
* The external API has endpoints like:
    * `POST /commits` to save many commits
    * `GET /commits` with query parameters (`provider`, `owner`, `repo`, `offset`, `limit`)
* It returns JSON, ideally grouped or paginated.

### CommitRepository
The only changes needed are the get, save, view and format interface methods in the [`CommitRepository`](source/app/Repositories/CommitRepository.php) 
must be updated to allow for requests to an external API. That is all.

### Benefits
* Zero changes to service and API classes.
* Clean separation of concerns.
* Easy to test and mock with fake HTTP responses.

### Other Potential Improvements

* Consider caching external results to reduce API calls.

---

## Imagine this mini-project needs microservices with one single database

Microservices should be **loosely coupled and independently deployable**. Sharing a database introduces 
coupling.

### Considerations

* **Avoid tight DB coupling:** If the database is shared, **encapsulate** all SQL access within one service per table.
* Define **clear service contracts**.
* Add **health checks** and **logging** per service.
* Use **OpenAPI / Swagger** to document service endpoints.


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

### Database

One **PostgreSQL** or **MySQL** instance with at least the following tables:

* `commits` (central table)
* `services_log` (for debug/errors)
* If supported, materialized views for reporting

> Each service **accesses the DB through its own repository layer** (no direct cross-service SQL).

### Communication Between Services

* Use **REST** or **gRPC** for internal service-to-service calls.
* Use **message queues** for async fetching, commit ingestion, or retries.

### Deployment Layout

```plaintext
/services
  /fetcher             # fetch commits
  /storage             # handles inserts
  /query               # read-only APIs
  /web-gateway         # UI + routes
```

> Use **containers** per service. Define their own `Dockerfile`, `composer.json`, and tests.
