<?php

namespace App\Services\GitHub;

use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use App\Services\AbstractVersionControlService;
use App\Services\VersionControlConnectorInterface;
use GuzzleHttp\Client;

class GitHubService extends AbstractVersionControlService
{
    public function __construct(protected string $owner, protected string $repo)
    {
    }

    public function getVersionControlService(): VersionControlConnectorInterface
    {
        return new GitHubConnector($this->client(), $this->repository(), $this->owner, $this->repo);
    }

    private function client(): Client
    {
        return new Client([
            'base_uri' => config('app.github.base_uri', 'https://api.github.com/'),
            'headers' => [
                'User-Agent' => config('app.github.headers.user_agent', 'GitHubCommitFetcherApp'),
                'Accept' => config('app.github.headers.accept', 'application/vnd.github+json'),
            ],
        ]);
    }

    private function repository(): MySqlCommitRepository
    {
        return new MySqlCommitRepository(new Commit());
    }
}