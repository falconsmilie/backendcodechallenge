<?php

namespace App\Api\GitHub;

use App\Api\ProviderApiInterface;
use App\Exceptions\CommitApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class GitHubApi implements ProviderApiInterface
{
    const int PER_PAGE_LIMIT = 100;

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
     *  take a few seconds. There's also a possibility to introduce caching if we are able to defer for a certain period
     *  The job could run at any period and the public "get route" could simply be a call to the CommitRepository
     *
     * @throws CommitApiException
     */
    public function mostRecentCommits(
        string $owner,
        string $repo,
        int $page = 1,
        int $perPage = 100,
        int $maxRetries = 3,
    ): array {
        $perPage = min($perPage, self::PER_PAGE_LIMIT);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->response($owner, $repo, $perPage, $page);
            } catch (GuzzleException $e) {
                if ($attempt === $maxRetries) {
                    throw new CommitApiException($e->getMessage(), $e->getCode(), $e);
                }

                continue;
            }

            $statusCode = $response->getStatusCode();
            $reason = $response->getReasonPhrase();

            try {
                $this->responseStatus($statusCode, $attempt, $maxRetries, $reason);
            } catch (CommitApiException $e) {
                if ($statusCode === 429 || ($statusCode >= 500 && $statusCode < 600)) {
                    continue;
                }
            }

            $body = $response->getBody()->getContents();

            try {
                $commits = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new CommitApiException($e->getMessage(), $e->getCode(), $e);
            }

            if (!is_array($commits)) {
                throw new CommitApiException('GitHub response could not be decoded into an array');
            }

            return [
                'commits' => $commits,
                'hasNextPage' => $this->hasNextPage($response),
            ];
        }

        throw new CommitApiException('Unexpected error fetching commits');
    }

    /**
     * @throws GuzzleException
     */
    private function response(string $owner, string $repo, int $perPage, int $page): ResponseInterface
    {
        return $this->client->get('repos/' . $owner . '/' . $repo . '/commits', [
            'query' => [
                'per_page' => $perPage,
                'page' => $page,
            ],
            'timeout' => 10,
        ]);
    }

    /**
     * @throws CommitApiException
     */
    private function responseStatus(int $statusCode, int $attempt, int $maxRetries, string $reason): void
    {
        if ($statusCode === 429 || ($statusCode >= 500 && $statusCode < 600)) {
            if ($attempt === $maxRetries) {
                throw new CommitApiException(
                    'GitHub says' . $statusCode . ' ' . $reason . ' after ' . $maxRetries . 'attempts'
                );
            }
        }

        if ($statusCode !== 200) {
            throw new CommitApiException('GitHub says: ' . $statusCode . '  ' . $reason);
        }
    }

    private function hasNextPage(ResponseInterface $response): bool
    {
        $hasNextPage = false;

        $linkHeader = $response->getHeaderLine('Link');

        if ($linkHeader) {
            $links = explode(',', $linkHeader);
            foreach ($links as $link) {
                if (preg_match('/<([^>]+)>;\s*rel="next"/', trim($link))) {
                    $hasNextPage = true;
                    break;
                }
            }
        }

        return $hasNextPage;
    }
}
