<?php

namespace Tests\Unit\Api\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Exceptions\CommitApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class GitHubApiTest extends TestCase
{
    private MockObject $mockClient;
    private GitHubApi $gitHubApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->getMockBuilder(Client::class)
            ->onlyMethods(['get'])
            ->getMock();

        $this->gitHubApi = new GitHubApi($this->mockClient);
    }

    public function testMostRecentCommitsReturnsDataAndDetectsNextPage(): void
    {
        $responseBody = json_encode([
            [
                'sha' => 'abc123',
                'commit' => [
                    'author' => ['name' => 'Alice', 'date' => '2023-01-01T00:00:00Z'],
                    'message' => 'Initial commit']
            ],
        ]);

        $response = new Response(
            200,
            ['Link' => '<https://api.github.com/repos/owner/repo/commits?page=2>; rel="next"'],
            $responseBody
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('repos/owner/repo/commits', $this->callback(function ($options) {
                return isset($options['query']['per_page'], $options['query']['page'])
                    && $options['query']['per_page'] <= 100
                    && $options['query']['page'] === 1;
            }))
            ->willReturn($response);

        $result = $this->gitHubApi->mostRecentCommits('owner', 'repo', 1, 50);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('commits', $result);
        $this->assertArrayHasKey('hasNextPage', $result);
        $this->assertCount(1, $result['commits']);
        $this->assertTrue($result['hasNextPage']);
    }

    public function testMostRecentCommitsHandlesNoNextPage(): void
    {
        $responseBody = json_encode([]);

        $response = new Response(
            200,
            [],
            $responseBody
        );

        $this->mockClient->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $result = $this->gitHubApi->mostRecentCommits('owner', 'repo');

        $this->assertIsArray($result);
        $this->assertEmpty($result['commits']);
        $this->assertFalse($result['hasNextPage']);
    }

    public function testMostRecentCommitsRetriesOnTransientErrors(): void
    {
        $responseBody = json_encode([]);

        $response = new Response(200, [], $responseBody);

        $this->mockClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(
                    new ConnectException('Connection timed out', $this->createMock(RequestInterface::class))
                ),
                $response
            );

        $result = $this->gitHubApi->mostRecentCommits('owner', 'repo', 1, 10, 2);

        $this->assertIsArray($result);
        $this->assertFalse($result['hasNextPage']);
    }

    public function testMostRecentCommitsThrowsExceptionAfterMaxRetries(): void
    {
        $this->expectException(CommitApiException::class);

        $this->mockClient->expects($this->exactly(3))
            ->method('get')
            ->willThrowException(new ConnectException('Network error', $this->createMock(RequestInterface::class)));

        $this->gitHubApi->mostRecentCommits('owner', 'repo', 1, 10, 3);
    }

    public function testMostRecentCommitsThrowsExceptionOnNon200Status(): void
    {
        $this->expectException(CommitApiException::class);

        $response = new Response(404, [], 'Not Found');

        $this->mockClient->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->gitHubApi->mostRecentCommits('owner', 'repo');
    }

    public function testMostRecentCommitsThrowsExceptionOnMalformedJson(): void
    {
        $this->expectException(CommitApiException::class);

        $response = new Response(200, [], 'Invalid JSON');

        $this->mockClient->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->gitHubApi->mostRecentCommits('owner', 'repo');
    }

    public function testMostRecentCommitsRetriesOnStatus429AndEventuallySucceeds(): void
    {
        $response429 = new Response(429, [], 'Too Many Requests');
        $response200 = new Response(200, [], json_encode([]));

        $this->mockClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $response429,
                $response200
            );

        $result = $this->gitHubApi->mostRecentCommits('owner', 'repo', 1, 10, 2);

        $this->assertIsArray($result);
        $this->assertFalse($result['hasNextPage']);
    }

    public function testMostRecentCommitsThrowsExceptionAfterMaxRetriesOnStatus429(): void
    {
        $this->expectException(CommitApiException::class);

        $response429 = new Response(429, [], 'Too Many Requests');

        $this->mockClient->expects($this->exactly(3))
            ->method('get')
            ->willReturn($response429);

        $this->gitHubApi->mostRecentCommits('owner', 'repo', 1, 10, 3);
    }
}
