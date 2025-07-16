<?php

namespace App\Factories;

use App\Exceptions\CommitServiceException;
use App\Services\CommitServiceInterface;
use App\Services\GitHub\GitHubService;

final class CommitFactory
{
    private const array PROVIDER_CREATORS = [
        'github' => self::class . '::createGitHubService',
//        'gitlab' => self::class . '::createGitLabService',
    ];

    public function __construct(
        private string $provider,
        private readonly string $owner,
        private readonly string $repo,
    ) {
        $this->provider = strtolower($this->provider);
    }

    /**
     * @throws CommitServiceException
     */
    public function make(): CommitServiceInterface
    {
        if (!isset(self::PROVIDER_CREATORS[$this->provider])) {
            throw new CommitServiceException('Provider "' . $this->provider . '" is not currently supported.');
        }

        $creator = self::PROVIDER_CREATORS[$this->provider];

        /** @var CommitServiceInterface $service */
        $service = call_user_func($creator, $this->owner, $this->repo);

        return $service;
    }

    private static function createGitHubService(string $owner, string $repo): CommitServiceInterface
    {
        return new GitHubService($owner, $repo);
    }
}
