<?php
namespace App\Services\GitHub;

use App\Contracts\CommitGetInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\Exceptions\VersionControlApiException;
use App\Services\Commit\BufferedCommitSaver;
use App\Services\VersionControlServiceInterface;

class GitHubConnector implements VersionControlServiceInterface
{
    private const string PROVIDER = 'github';
    private const int BATCH_SAVE_COUNT = 100;

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
    public function get(int $count = 100): bool
    {
        $perPage = min($count, config('app.github.requests.fetch_per_page_limit'));
        $pages = (int)ceil($count / $perPage);

        $commitSaver = new BufferedCommitSaver(
            $this->commitSaver,
            self::BATCH_SAVE_COUNT,
            $this->owner,
            $this->repo
        );

        return $this->commitGetter->mostRecentCommits($pages, $perPage, $commitSaver);
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

        return compact('commits', 'page', 'resultsPerPage', 'totalPages', 'totalCommits');
    }
}
