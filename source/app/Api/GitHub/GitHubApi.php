<?php

namespace App\Api\GitHub;

use App\Api\ProviderApiInterface;
use App\Exceptions\VersionControlApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class GitHubApi implements ProviderApiInterface
{
    public function __construct(private ?Client $client = null)
    {
        if ($this->client === null) {
            $this->client = new Client([
                'base_uri' => config('app.github.api_base_uri'),
                'headers' => [
                    'User-Agent' => config('app.github.headers.user_agent', 'GitHubCommitApp'),
                    'Accept' => config('app.github.headers.accept', 'application/vnd.github+json'),
                ],
            ]);
        }
    }

    /**
     * TODO: !!! This should be called from a proper job with retries, rate-limiting, events ... calling on demand can
     *  take a few seconds. There's also a possibility to introduce caching if we are able to defer for a certain period.
     *  The job could run at any period and the public "get route" could simply be a call to the MySqlCommitRepository.
     *
     * @throws VersionControlApiException
     */
    public function mostRecentCommits(
        string $owner,
        string $repo,
        int $page = 1,
        int $perPage = 100
    ): array {
        $perPage = min($perPage, 100);

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

        if ($response->getStatusCode() !== 200) {
            throw new VersionControlApiException("GitHub says: {$response->getStatusCode()}");
        }

        $body = $response->getBody()->getContents();

        try {
            $commits = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VersionControlApiException($e->getMessage(), $e->getCode(), $e);
        }

        if ($commits === null) {
            throw new VersionControlApiException(
                'Something went wrong reading the '.$owner.'/'.$repo .' repo. The commits returned were null.'
            );
        }

        return $commits;
    }
}