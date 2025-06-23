<?php

namespace Tests\Unit\Api\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Exceptions\CommitApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GitHubApiTest extends TestCase
{
    public function testMostRecentCommitsReturnsValidData(): void
    {
        $fakeCommits = [
            [
                'sha' => 'abc123',
                'commit' => [
                    'author' => [
                        'name' => 'Chris Cornell',
                        'date' => '2025-06-08T03:46:12Z',
                    ],
                    'message' => 'Black Hole Sun',
                ],
                'author' => [
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/chris',
                    'html_url' => 'https://github.com/chris',
                ],
                'html_url' => 'https://github.com/soundgarden/superunknown/commit/abc123',
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($fakeCommits)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $api = new GitHubApi($client);

        $result = $api->mostRecentCommits('soundgarden', 'superunknown');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('abc123', $result[0]['sha']);
    }

    public function testMostRecentCommitsThrowsOnNon200(): void
    {
        $mock = new MockHandler([
            new Response(500),
        ]);

        $client = new Client([
            'handler' => HandlerStack::create($mock),
            'http_errors' => false
        ]);

        $api = new GitHubApi($client);

        $this->expectException(CommitApiException::class);
        $this->expectExceptionMessage('GitHub says: 500');

        $api->mostRecentCommits('soundgarden', 'superunknown');
    }

    public function testMostRecentCommitsThrowsOnInvalidJson(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{not-json'),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $api = new GitHubApi($client);

        $this->expectException(CommitApiException::class);
        $api->mostRecentCommits('soundgarden', 'superunknown');
    }

    public function testMostRecentCommitsThrowsOnGuzzleException(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Request failed',
                new Request('GET', 'repos/soundgarden/superunknown/commits')
            ),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $api = new GitHubApi($client);

        $this->expectException(CommitApiException::class);
        $this->expectExceptionMessage('Request failed');

        $api->mostRecentCommits('soundgarden', 'superunknown');
    }
}
