<?php

namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use App\Services\AbstractVersionControlService;
use App\Services\VersionControlServiceInterface;

class GitHubService extends AbstractVersionControlService
{
    private GitHubApi $api;
    private GitHubApiGetter $commitGetter;
    private MySqlCommitRepository $commitSaver;
    private MySqlCommitRepository $commitViewer;

    public function __construct(
        protected string $owner,
        protected string $repo,
        ?GitHubApi $api = null,
        ?GitHubApiGetter $apiGetter = null,
        ?MySqlCommitRepository $commitRepository = null
    ) {
        $this->api = $api ?? new GitHubApi;

        $this->commitGetter = $apiGetter ?? new GitHubApiGetter($this->api, $this->owner, $this->repo);

        $this->commitSaver = $commitRepository ?? new MySqlCommitRepository(new Commit);

        $this->commitViewer = $this->commitSaver;
    }

    public function getVersionControlService(): VersionControlServiceInterface
    {
        return new GitHubConnector(
            $this->commitGetter,
            $this->commitSaver,
            $this->commitViewer,
            $this->owner,
            $this->repo
        );
    }
}