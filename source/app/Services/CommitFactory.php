<?php

namespace App\Services;

use App\Exceptions\CommitServiceException;
use App\Services\GitHub\GitHubService;

class CommitFactory
{
    private const array PROVIDERS = [
        'github' => GitHubService::class,
        // 'gitlab' => GitLabService::class,
        // 'bitbucket' => BitbucketService::class,
    ];

    public function __construct(protected string $provider, protected string $owner, protected string $repo)
    {
        $this->provider = strtolower($this->provider);
    }

    /**
     * @throws CommitServiceException
     */
    public function make(): AbstractCommitService
    {
        if (! isset(self::PROVIDERS[$this->provider])) {
            throw new CommitServiceException($this->provider . ' is not currently supported.');
        }

        $class = self::PROVIDERS[$this->provider];

        return new $class($this->owner, $this->repo);
    }
}
