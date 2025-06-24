# Implementation and Conceptual Questions
- [Implementation and Conceptual Questions](#implementation-and-conceptual-questions)
    * [Imagine this mini-project needs microservices with one single database](#imagine-this-mini-project-needs-microservices-with-one-single-database)
        + [Considerations](#considerations)
        + [Microservices](#microservices)
        + [Database](#database)
        + [Communication Between Services](#communication-between-services)
        + [Deployment Layout](#deployment-layout)
    * [How would your solution differ if you had to call another external API to store and receive the commits?](#how-would-your-solution-differ-if-you-had-to-call-another-external-api-to-store-and-receive-the-commits)
        + [Assumptions](#assumptions)
        + [`ExternalApiCommitRepository`](#externalapicommitrepository)
        + [How to Use It](#how-to-use-it)
        + [Benefits](#benefits)
        + [Other Potential Improvements](#other-potential-improvements)

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
* Use **message queues** for async fetching, commit ingestion, or retries.

---

### Deployment Layout

```plaintext
/services
  /fetcher             # fetch commits
  /storage             # handles inserts
  /query               # read-only APIs
  /web-gateway         # UI + routes
```

> Use **containers** per service. Define their own `Dockerfile`, `composer.json`, and tests.

---

## How would your solution differ if you had to call another external API to store and receive the commits?

### Assumptions

* There's an **external HTTP API** we call to store and retrieve commits.
* The external API accepts JSON arrays of commits (similar to `CommitDTO::toArray()`).
* The external API has endpoints like:
    * `POST /commits` to save many commits
    * `GET /commits` with query parameters (`provider`, `owner`, `repo`, `offset`, `limit`)
* It returns JSON, ideally grouped or paginated.

---

### ExternalApiCommitRepository

```php
<?php

namespace App\Repositories;

use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\DataTransferObjects\CommitDTO;
use App\Exceptions\CommitRepositoryException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

readonly class ExternalApiCommitRepository implements CommitSaveInterface, CommitViewInterface
{
    public function __construct(private Client $client)
    {
    }

    /**
     * @param CommitDTO[] $commits
     * @throws CommitRepositoryException
     */
    public function saveMany(array $commits): void
    {
        $payload = array_map(fn(CommitDTO $dto) => $dto->toArray(), $commits);

        try {
            $response = $this->client->post('/commits', [
                'json' => ['commits' => $payload],
            ]);
        } catch (GuzzleException $e) {
            throw new CommitRepositoryException('Failed to save commits to external API: ' . $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw new CommitRepositoryException("External API returned status {$response->getStatusCode()}");
        }
    }

    /**
     * @throws CommitRepositoryException
     */
    public function getByProviderGroupedByAuthor(
        int $offset,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null
    ): array {
        try {
            $response = $this->client->get('/commits', [
                'query' => array_filter([
                    'provider' => $provider,
                    'owner' => $owner,
                    'repo' => $repo,
                    'offset' => ($offset - 1) * $limit,
                    'limit' => $limit,
                ]),
            ]);
        } catch (GuzzleException $e) {
            throw new CommitRepositoryException('Failed to fetch commits from external API: ' . $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw new CommitRepositoryException("External API returned status {$response->getStatusCode()}");
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new CommitRepositoryException('Invalid response format from external API.');
        }

        return collect($data)->groupBy('author')->toArray();
    }

    public function countByProvider(string $provider, ?string $owner = null, ?string $repo = null): int
    {
        try {
            $response = $this->client->get('/commits/count', [
                'query' => array_filter([
                    'provider' => $provider,
                    'owner' => $owner,
                    'repo' => $repo,
                ]),
            ]);
        } catch (GuzzleException $e) {
            throw new CommitRepositoryException('Failed to count commits via external API: ' . $e->getMessage());
        }

        $data = json_decode($response->getBody()->getContents(), true);

        return (int) ($data['count'] ?? 0);
    }
}
```

---

### How to Use It

In `GitHubService`, we can inject `ExternalApiCommitRepository` instead of `MySqlCommitRepository`:

```php
$client = new Client(['base_uri' => 'https://external-commit-api.com']);
$repo = new ExternalApiCommitRepository($client);
```

Or build a `CommitRepositoryFactory`:

```php
class CommitRepositoryFactory
{
    public static function make(string $type): CommitSaveInterface & CommitViewInterface
    {
        return match ($type) {
            'db' => new MySqlCommitRepository(new Commit()),
            'external' => new ExternalApiCommitRepository(new Client([
                'base_uri' => 'https://external-commit-api.com',
                'headers' => ['Authorization' => 'Bearer ' . config('app.api_token')],
            ])),
            default => throw new \InvalidArgumentException("Unknown repo type: $type"),
        };
    }
}
```

---

### Benefits

* Zero changes to service classes.
* Clean separation of concerns.
* Easy to test and mock with fake HTTP responses.
* Swappable implementations via factory or container binding.

---

### Other Potential Improvements

* Consider caching external results to reduce API calls.
