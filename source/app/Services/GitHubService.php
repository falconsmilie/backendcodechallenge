<?php

namespace App\Services;

use GuzzleHttp\Client;

class GitHubService extends AbstractVersionControlService
{
    public function __construct(protected string $owner, protected string $repo)
    {
    }

    public function getVersionControlService(): VersionControlConnector
    {
        return new GitHubConnector($this->client(), $this->owner, $this->repo);
    }

    protected function client(): Client
    {
        return new Client([
            'base_uri' => config('app.github.base_uri', 'https://api.github.com/'),
            'headers' => [
                'User-Agent' => config('app.github.headers.user_agent', 'GitHubCommitFetcherApp'),
                'Accept' => config('app.github.headers.accept', 'application/vnd.github.v3+json'),
            ],
        ]);
    }
}