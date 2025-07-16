<?php

namespace App\Services\GitHub;

use App\Contracts\CommitGetInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\Exceptions\CommitRepositoryException;
use App\Exceptions\CommitServiceException;
use App\Models\Commit;
use App\Repositories\CommitRepository;
use App\Services\AbstractCommitService;
use App\Services\Commit\BufferedCommitSave;

class GitHubService extends AbstractCommitService
{
    private const string PROVIDER = 'github';
    private const int BATCH_INSERT_BUFFER_SIZE = 100;

    private CommitGetInterface $get;
    private CommitSaveInterface $save;
    private CommitViewInterface $view;

    public function __construct(
        protected string $owner,
        protected string $repo,
        ?CommitGetInterface $commitGetter = null,
        ?CommitSaveInterface $commitSaver = null,
        ?CommitViewInterface $commitViewer = null
    ) {
        $this->save = $commitSaver ?? new CommitRepository(new Commit);
        $this->get = $commitGetter  ?? $this->save;
        $this->view = $commitViewer ?? $this->save;
    }

    public function getCommits(GetParamsDTO $params): void
    {
        $perPage = (int)min($params->commitCount, config('app.github.requests.fetch_per_page_limit'));
        $pages = (int)ceil($params->commitCount / $perPage);

        $commitSaveHandler = new BufferedCommitSave($this->save, self::BATCH_INSERT_BUFFER_SIZE);

        try {
            $this->get->mostRecentCommits(
                self::PROVIDER,
                $this->owner,
                $this->repo,
                $pages,
                $perPage,
                $commitSaveHandler
            );
        } catch (CommitRepositoryException $e) {
            throw new CommitServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function viewCommits(PaginationDTO $pagination): array
    {
        $error = null;
        $commits = [];
        $totalCommits = 0;
        $totalPages = 0;

        try {
            $commits = $this->view->getByProviderGroupedByAuthor(
                $pagination->page,
                $pagination->resultsPerPage,
                self::PROVIDER,
                $this->owner,
                $this->repo
            );
        } catch (CommitRepositoryException $e) {
            $error = $e->getMessage();
        }

        if ($error === null) {
            $totalCommits = $this->view->countByProvider(self::PROVIDER, $this->owner, $this->repo);
            if ($totalCommits) {
                $totalPages = (int)ceil($totalCommits / $pagination->resultsPerPage);
            }
        }

        return [
            'commits' => $commits,
            'error' => $error,
            'page' => $pagination->page,
            'resultsPerPage' => $pagination->resultsPerPage,
            'totalPages' => $totalPages,
            'totalCommits' => $totalCommits,
        ];
    }
}
