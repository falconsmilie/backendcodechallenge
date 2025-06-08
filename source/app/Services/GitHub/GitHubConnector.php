<?php

namespace App\Services\GitHub;

use App\Exceptions\VersionControlException;
use App\Jobs\GitHub\FetchCommitsJob;
use App\Repositories\MySqlCommitRepository;
use App\Services\VersionControlConnectorInterface;
use GuzzleHttp\Client;

class GitHubConnector implements VersionControlConnectorInterface
{
    private const string PROVIDER = 'github';
    private Client $client;
    private MySqlCommitRepository $commits;

    public function __construct(
        Client $client,
        MySqlCommitRepository $commits,
        protected string $owner,
        protected string $repo
    ) {
        $this->client = $client;
        $this->commits = $commits;
    }

    public function view(int $resultsPerPage = 100): array
    {
        // TODO: we need to validate the 'page' parameter
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $commits = $this->commits->getByProviderGroupedByAuthor(
            $page,
            $resultsPerPage,
            self::PROVIDER,
            $this->owner,
            $this->repo
        );

        $totalCommits = $this->commits->countByProvider(self::PROVIDER, $this->owner, $this->repo);

        $totalPages = (int)ceil($totalCommits / $resultsPerPage);

        return compact('commits', 'page', 'totalPages', 'totalCommits');
    }

    /**
     * Things to consider for the future; this should be extracted into a proper job with retries, rate-limiting ...
     * The job could send events we listen for in the frontend
     *
     * @throws VersionControlException
     */
    public function get(int $count = 100): array
    {
        return new FetchCommitsJob($this->client, $count)
            ->handle(
                self::PROVIDER,
                $this->owner,
                $this->repo,
                config('app.github.requests.fetch_per_page_limit', 100)
            );
    }
}
