<?php

namespace App\Services;

use App\Exceptions\CommitServiceException;
use App\Services\GitHub\GitHubService;

class CommitFactory
{
    public function __construct(protected string $provider, protected string $owner, protected string $repo)
    {
    }

    /**
     * @throws CommitServiceException
     */
    public function make(): AbstractCommitService
    {
        switch ($this->provider) {
            case 'github':
                return new GitHubService($this->owner, $this->repo);

            case 'gitlab':
            case 'bitbucket':
                break;
        }

        throw new CommitServiceException($this->provider.' is not currently supported.');
    }
}
