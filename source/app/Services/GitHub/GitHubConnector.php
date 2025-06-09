<?php

namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Exceptions\VersionControlApiException;
use App\Exceptions\VersionControlServiceException;
use App\Models\Commit;
use App\Repositories\CommitRepositoryInterface;
use App\Repositories\MySqlCommitRepository;
use App\Services\VersionControlServiceInterface;
use DateTimeImmutable;
use DateTimeZone;

class GitHubConnector implements VersionControlServiceInterface
{
    private const string PROVIDER = 'github';

    public function __construct(
        protected string $owner,
        protected string $repo,
        private ?CommitRepositoryInterface $commits = null,
        private ?GitHubApi $githubApi = null,
    ) {
        if ($this->commits === null) {
            $this->commits = new MySqlCommitRepository(new Commit());
        }
        if ($this->githubApi === null) {
            $this->githubApi = new GitHubApi();
        }
    }

    public function view(int $resultsPerPage = 100): array
    {
        // TODO: validate the 'page' parameter
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
     * @throws VersionControlServiceException
     */
    public function get(int $count = 100): array
    {
        $perPage = min($count, config('app.github.requests.fetch_per_page_limit'));
        $pages = (int) ceil($count / $perPage);

        $commitHashes = [];

        for ($page = 1; $page <= $pages; $page++) {

            $commits = $this->query($count, $page, $perPage, $this->owner, $this->repo);

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $formatted = $this->formatCommit(
                        self::PROVIDER,
                        $this->owner,
                        $this->repo,
                        $commit
                    );
                    $commitHashes[] = $formatted;
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
     * @throws \DateMalformedStringException
     */
    protected function formatCommit(string $provider, string $owner, string $repo, array $commit): array
    {
        return [
            'provider' => $provider,
            'owner' => $owner,
            'repo' => $repo,
            'hash' => $commit['sha'],
            'author' => $commit['commit']['author']['name'] ?? 'Unknown',
            'author_avatar_url' => $commit['author']['avatar_url'] ?? '',
            'author_html_url' => $commit['author']['html_url'] ?? '',
            'commit_date' => $commit['commit']['author']['date'],
            'commit_message' => $commit['commit']['message'],
            'commit_html_url' => $commit['html_url'],
            'created_at' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
            'updated_at' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
        ];
    }

    /**
     * @throws VersionControlServiceException
     */
    private function query(int $count, int $page, mixed $perPage, string $owner, string $repo): array
    {
        try {
            return $this->githubApi->getRecentCommits($owner, $repo, $count, $page, $perPage);

        } catch (VersionControlApiException $e) {
            throw new VersionControlServiceException($e->getMessage());
        }
    }
}
