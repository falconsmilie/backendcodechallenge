<?php

namespace App\Api\GitHub;

use App\Api\ProviderApiInterface;
use App\Exceptions\VersionControlApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GitHubApi implements ProviderApiInterface
{
    public function __construct(
        private ?Client $client = null
    ) {
        if ($this->client === null) {
            $this->client = new Client([
                'base_uri' => config('app.github.api_base_uri', 'https://api.github.com/'),
                'headers' => [
                    'User-Agent' => config('app.github.headers.user_agent', 'GitHubCommitFetcherApp'),
                    'Accept' => config('app.github.headers.accept', 'application/vnd.github+json'),
                ],
            ]);
        }
    }

    /**
     * TODO: This should be a proper job with retries, rate-limiting, events ... calling on demand can take a few
     *  seconds. There's also a possibility to introduce caching if we are able to defer for a certain period.
     *  The job could run at any period and the "get" would simply be a call to the MySqlCommitRepository.
     *
     * @throws VersionControlApiException
     */
    public function getRecentCommits(
        string $owner,
        string $repo,
        int $count = 100,
        int $page = 1,
        int $perPage = 100
    ): array {
        try {
            $response = $this->client->get('repos/'.$owner.'/'.$repo.'/commits', [
                'query' => [
                    'per_page' => $perPage,
                    'page' => $page,
                ],
            ]);
        } catch (GuzzleException $e) {
            // TODO: log the errors
            throw new VersionControlApiException($e->getMessage(), $e->getCode(), $e);
        }

        $commits = json_decode($response->getBody()->getContents(), true);

        if ($commits === null) {
            throw new VersionControlApiException(
                'Something went wrong reading the '.$owner.'/'.$repo .' repo.'
            );
        }

        return $commits;
    }
}