<?php

namespace App\Services;

use App\Exceptions\VersionControlException;
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
                throw new VersionControlException($this->provider . ' is not currently supported.');
        }

        throw new VersionControlException($this->provider . ' is not currently supported.');
    }
}