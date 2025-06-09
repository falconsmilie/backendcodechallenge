<?php

namespace App\Services;

use App\Exceptions\VersionControlServiceException;
use App\Services\GitHub\GitHubService;
use Exception;

class VersionControlFactory
{
    public function __construct(protected string $provider, protected string $owner, protected string $repo)
    {
    }

    /**
     * @throws Exception
     */
    public function make(): AbstractVersionControlService
    {
        switch ($this->provider) {
            case 'github':
                return new GitHubService($this->owner, $this->repo);

            case 'gitlab':
            case 'bitbucket':
                break;
        }

        throw new VersionControlServiceException($this->provider.' is not currently supported.');
    }
}
