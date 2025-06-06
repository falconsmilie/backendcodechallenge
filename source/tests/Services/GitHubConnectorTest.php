<?php

namespace Tests\Services;

use App\Services\GitHubConnector;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GitHubConnectorTest extends TestCase
{
    public function testViewReturnsExpectedData()
    {
        $mockCommitsGrouped = collect([
            'Alice' => [
                ['hash' => 'abc123', 'author' => 'Alice'],
                ['hash' => 'def456', 'author' => 'Alice'],
            ],
            'Bob' => [
                ['hash' => 'xyz789', 'author' => 'Bob'],
            ],
        ]);

        $mock = $this->getMockBuilder(GitHubConnector::class)
            ->onlyMethods(['getCommits', 'countCommits'])
            ->getMock();

        // Mock getCommits to return our grouped commits
        $mock->method('getCommits')
            ->willReturn($mockCommitsGrouped);

        // Mock countCommits to return total commits count = 3
        $mock->method('countCommits')
            ->willReturn(3);

        // Simulate $_GET['page'] not set, so page = 1
        unset($_GET['page']);

        $resultsPerPage = 2;

        $result = $mock->view($resultsPerPage);

        $this->assertArrayHasKey('commits', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('totalCommits', $result);

        $this->assertSame(1, $result['page']);
        $this->assertSame(3, $result['totalCommits']);

        $this->assertEquals($mockCommitsGrouped, $result['commits']);
    }
//
//    public function testViewPageParameterBelowOneDefaultsToOne()
//    {
//        $mock = $this->getMockBuilder(GitHubConnector::class)
//            ->onlyMethods(['queryCommits', 'countCommits'])
//            ->getMock();
//
//        $mock->method('queryCommits')->willReturn([]);
//        $mock->method('countCommits')->willReturn(0);
//
//        $_GET['page'] = -5;
//
//        $result = $mock->view(10);
//
//        $this->assertSame(1, $result['page']);
//    }
//
//    public function testViewPageParameterIsUsedIfValid()
//    {
//        $mock = $this->getMockBuilder(GitHubConnector::class)
//            ->onlyMethods(['queryCommits', 'countCommits'])
//            ->getMock();
//
//        $mock->method('queryCommits')->willReturn([]);
//        $mock->method('countCommits')->willReturn(0);
//
//        $_GET['page'] = 3;
//
//        $result = $mock->view(10);
//
//        $this->assertSame(3, $result['page']);
//    }

    public function testFetchReturnsCommitsFromGitHub()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode([
            ['sha' => 'abc123', 'commit' => ['author' => ['name' => 'Alice']]],
            ['sha' => 'def456', 'commit' => ['author' => ['name' => 'Bob']]],
        ]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn($response);

        $mock = $this->getMockBuilder(GitHubConnector::class)
            ->onlyMethods(['client', 'response', 'saveCommit'])
            ->getMock();

        $mock->method('client')->willReturn($client);
        $mock->method('response')->willReturn($response);

        $calls = [];
        $mock->expects($this->exactly(2))
            ->method('saveCommit')
            ->willReturnCallback(function ($commit) use (&$calls) {
                $calls[] = $commit;

                return null;
            });

        $result = $mock->get(200);

        $this->assertCount(2, $calls);
        $this->assertSame(['hash' => 'abc123', 'author' => 'Alice'], $calls[0]);
        $this->assertSame(['hash' => 'def456', 'author' => 'Bob'], $calls[1]);

        $this->assertCount(2, $result);
        $this->assertSame('abc123', $result[0]['hash']);
    }
}
