<?php

namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Api\ProviderApiInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\DataTransferObjects\CommitDTO;
use App\Exceptions\CommitApiException;
use App\Exceptions\CommitRepositoryException;
use App\Exceptions\CommitServiceException;
use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use App\Services\AbstractCommitService;
use App\Services\Commit\BufferedCommitSave;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;

class GitHubService extends AbstractCommitService
{
    private const string PROVIDER = 'github';
    private const int BATCH_INSERT_BUFFER_SIZE = 100;

    private ProviderApiInterface $commitGetter;
    private CommitSaveInterface $commitSaver;
    private CommitViewInterface $commitViewer;

    public function __construct(
        protected string $owner,
        protected string $repo,
        ?ProviderApiInterface $api = null,
        ?CommitSaveInterface $commitRepository = null
    ) {
        $this->commitGetter = $api ?? new GitHubApi;
        $this->commitSaver = $commitRepository ?? new MySqlCommitRepository(new Commit);
        $this->commitViewer = $this->commitSaver;
    }

    /**
     * @throws CommitServiceException
     */
    public function getCommits(int $count = 100): bool
    {
        $perPage = min($count, config('app.github.requests.fetch_per_page_limit'));
        $pages = (int)ceil($count / $perPage);

        $commitSaveHandler = new BufferedCommitSave($this->commitSaver, self::BATCH_INSERT_BUFFER_SIZE);

        return $this->mostRecentCommits($pages, $perPage, $commitSaveHandler);
    }

    /**
     * @throws CommitRepositoryException
     */
    public function viewCommits(int $page = 1, int $resultsPerPage = 100): array
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

    /**
     * @throws CommitServiceException
     */
    private function mostRecentCommits(int $pages, int $perPage, callable $processCommit): bool
    {
        for ($page = 1; $page <= $pages; $page++) {
            $commitCount = 0;

            try {
                $commits = $this->commitGetter->mostRecentCommits($this->owner, $this->repo, $page, $perPage);
            } catch (CommitApiException $e) {
//                $retries++;
//                $page--;
//
//                if ($retries >= self::API_RETRIES) {
//                    throw new CommitServiceException($e->getMessage(), $e->getCode(), $e);
//                }
                // TODO: let's just go to the next page for now ...
                continue;
            }

            if (empty($commits)) {
                break;
            }

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commit = $this->format($commit);
                    $processCommit($commit);
                    $commitCount++;
                }
            }

            if ($commitCount < $perPage) {
                break;
            }
        }

        return true;
    }

    /**
     * @throws CommitServiceException
     */
    private function format(array $rawCommit): CommitDTO
    {
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s');

            $commitDate = new DateTimeImmutable($rawCommit['commit']['author']['date'])
                ->format('Y-m-d H:i:s');
        } catch (DateMalformedStringException $e) {
            throw new CommitServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return new CommitDTO(
            provider: 'github',
            owner: $this->owner,
            repo: $this->repo,
            hash: $rawCommit['sha'],
            author: $rawCommit['commit']['author']['name'] ?? 'Unknown',
            authorAvatarUrl: $rawCommit['author']['avatar_url'] ?? null,
            authorHtmlUrl: $rawCommit['author']['html_url'] ?? null,
            commitDate: $commitDate,
            commitMessage: $rawCommit['commit']['message'] ?? null,
            commitHtmlUrl: $rawCommit['html_url'] ?? null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
