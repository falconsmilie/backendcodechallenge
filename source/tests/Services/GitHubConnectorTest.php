<?php

namespace Tests\Services;

use App\Exceptions\VersionControlException;
use App\Models\Commit;
use App\Services\GitHubConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class GitHubConnectorTest extends TestCase
{
    public function testGetFetchesAndParsesCommitsCorrectly()
    {
        $fakeCommit = [
            [
                'sha' => 'abc123',
                'commit' => [
                    'author' => [
                        'name' => 'Jane Doe',
                        'date' => '2023-01-01T12:00:00Z',
                    ],
                    'message' => 'Initial commit',
                ],
                'author' => [
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/1?v=4',
                    'html_url' => 'https://github.com/janedoe',
                ],
                'html_url' => 'https://github.com/janedoe/repo/commit/abc123',
            ]
        ];

        $json = json_encode($fakeCommit);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn($json);

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('getBody')->willReturn($streamMock);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('get')->willReturn($responseMock);

        $connector = $this->getMockBuilder(GitHubConnector::class)
            ->setConstructorArgs([$clientMock, 'owner', 'repo'])
            ->onlyMethods(['saveCommits'])
            ->getMock();

        $connector->expects($this->once())->method('saveCommits');

        $result = $connector->get(1);

        $this->assertCount(1, $result);
        $this->assertEquals('abc123', $result[0]['hash']);
        $this->assertEquals('Jane Doe', $result[0]['author']);
    }

    public function testViewReturnsPaginationStructure()
    {
        $_GET['page'] = 2;

        $clientStub = $this->createStub(Client::class);

        $connector = $this->getMockBuilder(GitHubConnector::class)
            ->setConstructorArgs([$clientStub, 'owner', 'repo'])
            ->onlyMethods(['getCommits', 'countCommits'])
            ->getMock();

        $connector->method('getCommits')->willReturn(collect(['commits...']));
        $connector->method('countCommits')->willReturn(250);

        $result = $connector->view(100);

        $this->assertEquals(2, $result['page']);
        $this->assertEquals(3, $result['totalPages']);
        $this->assertEquals(250, $result['totalCommits']);
        $this->assertEquals(['commits...'], $result['commits']->all());
    }
}
