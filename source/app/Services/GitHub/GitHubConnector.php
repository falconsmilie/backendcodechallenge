<?php
namespace App\Services\GitHub;

use App\Contracts\CommitGetInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\Exceptions\VersionControlApiException;
use App\Services\VersionControlServiceInterface;
use DateTimeImmutable;
use DateTimeZone;

class GitHubConnector implements VersionControlServiceInterface
{
    private const string PROVIDER = 'github';

    public function __construct(
        private readonly CommitGetInterface $commitGetter,
        private readonly CommitSaveInterface $commitSaver,
        private readonly CommitViewInterface $commitViewer,
        private readonly string $owner,
        private readonly string $repo,
    ) {}

    /**
     * @throws VersionControlApiException
     */
    public function get(int $count = 100): array
    {
        $perPage = min($count, config('app.github.requests.fetch_per_page_limit', 100));
        $pages = (int)ceil($count / $perPage);

        $rawCommits = $this->commitGetter->mostRecentCommits($count, $pages, $perPage);

        $commits = [];

        foreach ($rawCommits as $commit) {
            $commits[] = $this->formatCommit($commit);
        }

        $this->commitSaver->saveMany($commits);

        return $commits;
    }

    public function view(int $page = 1, int $resultsPerPage = 100): array
    {
        $commits = $this->commitViewer->getByProviderGroupedByAuthor(
            $page,
            $resultsPerPage,
            self::PROVIDER,
            $this->owner,
            $this->repo
        );

        $totalCommits = $this->commitViewer->countByProvider(self::PROVIDER, $this->owner, $this->repo);

        $totalPages = (int)ceil($totalCommits / $resultsPerPage);

        return compact('commits', 'page', 'totalPages', 'totalCommits');
    }

    private function formatCommit(array $commit): array
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
            'created_at' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
            'updated_at' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
        ];
    }
}
