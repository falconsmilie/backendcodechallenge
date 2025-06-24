<?php

namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Api\ProviderApiInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\DataTransferObjects\CommitDTO;
use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\Exceptions\CommitApiException;
use App\Exceptions\CommitRepositoryException;
use App\Exceptions\CommitServiceException;
use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use App\Services\AbstractCommitService;
use App\Services\Commit\BufferedCommitSave;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

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

    public function getCommits(GetParamsDTO $params): bool
    {
        $perPage = min($params->commitCount, config('app.github.requests.fetch_per_page_limit'));
        $pages = (int)ceil($params->commitCount / $perPage);

        $commitSaveHandler = new BufferedCommitSave($this->commitSaver, self::BATCH_INSERT_BUFFER_SIZE);

        return $this->mostRecentCommits($pages, $perPage, $commitSaveHandler);
    }

    public function viewCommits(PaginationDTO $pagination): array
    {
        $error = null;
        $commits = [];

        try {
            $commits = $this->commitViewer->getByProviderGroupedByAuthor(
                $pagination->page,
                $pagination->resultsPerPage,
                self::PROVIDER,
                $this->owner,
                $this->repo
            );
        } catch (CommitRepositoryException $e) {
            $error = $e->getMessage();
        }

        $totalCommits = $this->commitViewer->countByProvider(self::PROVIDER, $this->owner, $this->repo);

        $totalPages = (int)ceil($totalCommits / $pagination->resultsPerPage);

        return [
            'commits' => $commits,
            'error' => $error,
            'page' => $pagination->page,
            'resultsPerPage' => $pagination->resultsPerPage,
            'totalPages' => $totalPages,
            'totalCommits' => $totalCommits,
        ];
    }

    /**
     * @throws CommitServiceException
     */
    private function mostRecentCommits(int $pages, int $perPage, callable $processCommit): bool
    {
        for ($page = 1; $page <= $pages; $page++) {
            $commitCount = 0;

            try {
                $result = $this->commitGetter->mostRecentCommits($this->owner, $this->repo, $page, $perPage);
                $commits = $result['commits'];
                $hasNextPage = $result['hasNextPage'];
            } catch (CommitApiException $e) {
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

            if ($commitCount < $perPage || !$hasNextPage) {
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
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

            $commitDate = new DateTimeImmutable($rawCommit['commit']['author']['date']);
        } catch (Exception $e) {
            throw new CommitServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return new CommitDTO(
            provider: self::PROVIDER,
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
