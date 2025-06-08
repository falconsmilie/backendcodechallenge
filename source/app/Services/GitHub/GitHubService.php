<?php

namespace App\Services\GitHub;

use App\Services\AbstractVersionControlService;
use App\Services\VersionControlServiceInterface;

class GitHubService extends AbstractVersionControlService
{
    public function __construct(protected string $owner, protected string $repo)
    {
    }

    public function getVersionControlService(): VersionControlServiceInterface
    {
        return new GitHubConnector($this->owner, $this->repo);
    }
}