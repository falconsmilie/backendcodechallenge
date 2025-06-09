<?php

namespace Tests\Unit\Api\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Exceptions\VersionControlApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class GitHubApiTest extends TestCase
{
    private Client&MockObject $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
    }

    public function testGetRecentCommitsReturnsArrayOnSuccess(): void
    {
        $jsonData = [
            ['sha' => '123', 'commit' => ['message' => 'Initial commit']],
            ['sha' => '456', 'commit' => ['message' => 'Second commit']],
        ];
        $jsonString = json_encode($jsonData);

        $mockResponse = $this->createMock(Response::class);
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn($jsonString);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('repos/owner/repo/commits', ['query' => ['per_page' => 100, 'page' => 1]])
            ->willReturn($mockResponse);

        $api = new GitHubApi($this->mockClient);

        $result = $api->getRecentCommits('owner', 'repo', 100, 1, 100);

        $this->assertSame($jsonData, $result);
    }

    public function testGetRecentCommitsThrowsExceptionWhenGuzzleThrows(): void
    {
        $this->mockClient->method('get')->willThrowException(
            new class('error message', 500) extends \Exception implements GuzzleException {}
        );

        $api = new GitHubApi($this->mockClient);

        $this->expectException(VersionControlApiException::class);
        $this->expectExceptionMessage('error message');

        $api->getRecentCommits('owner', 'repo');
    }

    public function testGetRecentCommitsThrowsExceptionOnInvalidJson(): void
    {
        $invalidJson = 'invalid json';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn($invalidJson);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('get')->willReturn($mockResponse);

        $api = new GitHubApi($this->mockClient);

        $this->expectException(VersionControlApiException::class);
        $this->expectExceptionMessageMatches('/Something went wrong reading/');

        $api->getRecentCommits('owner', 'repo');
    }
}
