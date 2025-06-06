<?php

namespace App\Services;

use App\Exceptions\VersionControlException;
use App\Models\Commit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

class GitHubConnector implements VersionControlConnector
{
    public function __construct(protected Client $client, protected string $owner, protected string $repo)
    {
    }

    // @see https://docs.github.com/en/rest/commits/commits?apiVersion=2022-11-28#list-commits
    private const int GITHUB_FETCH_PER_PAGE_LIMIT = 100;

    public function view(int $resultsPerPage = 100): array
    {
        // TODO: we need to validate the 'page' parameter
        $page = isset($_GET['page'])
            ? max(1, (int)$_GET['page'])
            : 1;

        $commits = $this->getCommits($page, $resultsPerPage);
        $totalCommits = $this->countCommits();
        $totalPages = ceil($totalCommits / $resultsPerPage);

        return compact('commits', 'page', 'totalPages', 'totalCommits');
    }

    /**
     * Things to consider for the future; retries, rate-limiting
     *
     * @throws VersionControlException
     */
    public function get(int $count = 1000): array
    {
        $perPage = min($count, self::GITHUB_FETCH_PER_PAGE_LIMIT);
        $pages = ceil($count / $perPage);

        $commitHashes = [];

        for ($page = 1; $page <= $pages; $page++) {
            $response = $this->response(
                $this->client,
                $this->owner.'/'.$this->repo,
                $page,
                $perPage
            );

            $commits = json_decode($response->getBody()->getContents(), true);

            if ($commits === null) {
                throw new VersionControlException('Something went wrong reading the requested repo.');
            }

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commitHashes[] = [
                        'provider' => 'github',
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

            if (count($commits) < $perPage) {
                break;
            }
        }

//        foreach ($commitHashes as $commit) {
            $this->saveCommits($commitHashes);
//        }

        return $commitHashes;
    }

    /**
     * @throws VersionControlException
     */
    protected function response(Client $client, string $repo, int $page, int $perPage): ResponseInterface
    {
        try {
            $response = $client->get('repos/' . $repo . '/commits', [
                'query' => [
                    'per_page' => $perPage,
                    'page' => $page,
                ],
            ]);
        } catch (GuzzleException $e) {
            // TODO: log this error
            throw new VersionControlException($e->getMessage(), $e->getCode(), $e);
        }

        return $response;
    }

    protected function saveCommits(array $commits): void
    {
        collect($commits)
            ->chunk(500)
            ->each(function ($chunk) {
                Commit::insertOrIgnore($chunk->toArray());
            });
    }

    public function getCommits(int $page, int $resultsPerPage): Collection
    {
        $query = Commit::where('provider', 'github');

        if ($this->owner) {
            $query->where('owner', $this->owner);
        }

        if ($this->repo) {
            $query->where('repo', $this->repo);
        }

        return $query->orderBy('commit_date', 'desc')
            ->skip(($page - 1) * $resultsPerPage)
            ->take($resultsPerPage)
            ->get()
            ->groupBy('author');
    }

    public function countCommits(): int
    {
        return Commit::count();
    }
}
