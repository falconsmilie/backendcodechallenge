<?php
namespace Tests\Unit\Services\GitHub;

use App\Exceptions\VersionControlException;
use App\Repositories\MySqlCommitRepository;
use App\Services\GitHub\GitHubConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class GitHubConnectorTest extends TestCase
{
    #[Test]
    public function testGetFetchesAndParsesCommits(): void
    {
        $fakeCommit = [[
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
        ]];

        $json = json_encode($fakeCommit);

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn($json);

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('getBody')->willReturn($streamMock);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('get')->willReturn($responseMock);

        $repositoryMock = $this->createMock(MySqlCommitRepository::class);
        $repositoryMock->expects($this->once())
            ->method('saveMany')
            ->with($this->callback(function ($commits) {
                return is_array($commits) &&
                    count($commits) === 1 &&
                    $commits[0]['hash'] === 'abc123';
            }));

        $connector = new GitHubConnector($clientMock, $repositoryMock, 'owner', 'repo');

        $result = $connector->get(1);

        $this->assertCount(1, $result);
        $this->assertEquals('abc123', $result[0]['hash']);
        $this->assertEquals('Jane Doe', $result[0]['author']);
    }

    #[Test]
    public function testClientException(): void
    {
        $this->expectException(VersionControlException::class);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('get')
            ->willThrowException(new RequestException("API Error", new Request('GET', 'test')));

        $repositoryMock = $this->createMock(MySqlCommitRepository::class);

        $service = new GitHubConnector($clientMock, $repositoryMock, 'owner', 'repo');
        $service->get(1);
    }

    #[Test]
    public function testViewReturnsPagination(): void
    {
        $_GET['page'] = 2;

        $clientStub = $this->createStub(Client::class);

        $repositoryMock = $this->createMock(MySqlCommitRepository::class);
        $repositoryMock->method('getByProviderGroupedByAuthor')
            ->with(2, 100, 'github', 'owner', 'repo')
            ->willReturn(['commit1', 'commit2']);
        $repositoryMock->method('countByProvider')
            ->with('github', 'owner', 'repo')
            ->willReturn(250);

        $connector = new GitHubConnector($clientStub, $repositoryMock, 'owner', 'repo');

        $result = $connector->view(100);

        $this->assertEquals(2, $result['page']);
        $this->assertEquals(3, $result['totalPages']);
        $this->assertEquals(250, $result['totalCommits']);
        $this->assertEquals(['commit1', 'commit2'], $result['commits']);
    }

    #[Test]
    public function testInvalidJsonThrowsException(): void
    {
        $this->expectException(VersionControlException::class);
        $this->expectExceptionMessage('Something went wrong reading the owner/repo repo.');

        // Simulate invalid JSON response
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn('INVALID_JSON');

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('getBody')->willReturn($streamMock);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('get')->willReturn($responseMock);

        $repoMock = $this->createMock(MySqlCommitRepository::class);
        $repoMock->expects($this->never())->method('saveMany');

        $connector = new GitHubConnector($clientMock, $repoMock, 'owner', 'repo');
        $connector->get(1);
    }
}
