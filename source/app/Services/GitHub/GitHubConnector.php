<?php

namespace App\Services\GitHub;

use App\Exceptions\VersionControlException;
use App\Repositories\MySqlCommitRepository;
use App\Services\VersionControlConnectorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GitHubConnector implements VersionControlConnectorInterface
{
    // @see https://docs.github.com/en/rest/commits/commits?apiVersion=2022-11-28#list-commits
    private const int GITHUB_FETCH_PER_PAGE_LIMIT = 100;

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

        $totalPages = ceil($totalCommits / $resultsPerPage);

        return compact('commits', 'page', 'totalPages', 'totalCommits');
    }

    /**
     * Things to consider for the future; a job with retries, rate-limiting ... we need to determine if the results
     * must be on demand or if delays are acceptable.
     *
     * @throws VersionControlException
     */
    public function get(int $count = 100): array
    {
        $perPage = min($count, self::GITHUB_FETCH_PER_PAGE_LIMIT);
        $pages = ceil($count / $perPage);

        $commitHashes = [];

        for ($page = 1; $page <= $pages; $page++) {

            $commits = $this->response($page, $perPage);

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commitHashes[] = $this->formatCommit($commit);
                }
            }

            if (count($commitHashes) < $perPage) {
                break;
            }
        }

        $this->commits->saveMany($commitHashes);

        return $commitHashes;
    }

    /**
     * @throws VersionControlException
     */
    protected function response(int $page, int $perPage): array
    {
        try {
            $response = $this->client->get('repos/'.$this->owner.'/'.$this->repo.'/commits', [
                'query' => [
                    'per_page' => $perPage,
                    'page' => $page,
                ],
            ]);
        } catch (GuzzleException $e) {
            // TODO: log the errors
            throw new VersionControlException($e->getMessage(), $e->getCode(), $e);
        }

        $commits = json_decode($response->getBody()->getContents(), true);

        if ($commits === null) {
            throw new VersionControlException(
                'Something went wrong reading the '.$this->owner.'/'.$this->repo .' repo.'
            );
        }

        return $commits;
    }

    protected function formatCommit(array $commit): array
    {
        return [
            'provider' => self::PROVIDER,
            'owner' => $this->owner,
            'repo' => $this->repo,
            'hash' => $commit['sha'],
            'author' => $commit['commit']['author']['name'] ?? 'Unknown',
            'author_avatar_url' => $commit['author']['avatar_url'] ?? '',
            'author_html_url' => $commit['author']['html_url'] ?? '',
            'commit_date' => $commit['commit']['author']['date'],
            'commit_message' => $commit['commit']['message'],
            'commit_html_url' => $commit['html_url'],
        ];
    }
}
