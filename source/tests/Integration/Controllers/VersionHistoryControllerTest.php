<?php

namespace Tests\Integration\Controllers;

use App\Controllers\VersionHistoryController;
use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\Exceptions\CommitServiceException;
use App\Services\AbstractCommitService;
use App\Services\CommitFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class VersionHistoryControllerTest extends TestCase
{
    private CommitFactory $commitFactory;

    protected function setUp(): void
    {
        $_GET = [];
        $this->commitFactory = $this->createMock(CommitFactory::class);
    }

    public function testIndexRendersIndexView(): void
    {
        ob_start();
        $controller = new VersionHistoryController($this->commitFactory);
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Commit Viewer</title>', $output);
    }

    public function testViewOutputsCommitList(): void
    {
        $_GET['page'] = 1;
        $_GET['results_per_page'] = 50;

        $mockService = $this->createMock(AbstractCommitService::class);
        $mockService->expects($this->once())
            ->method('viewCommits')
            ->with($this->isInstanceOf(PaginationDTO::class))
            ->willReturn([
                'commits' => [
                    'Kurt Cobain' => [[
                        'hash' => 'abc123',
                        'author' => 'Kurt Cobain',
                        'commit_message' => 'Smells Like Teen Spirit',
                        'repo' => 'nevermind',
                        'owner' => 'nirvana',
                        'commit_date' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'author_avatar_url' => '',
                        'author_html_url' => '',
                        'commit_html_url' => '',
                        'id' => 1,
                        'provider' => 'github',
                        'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ]]
                ],
                'page' => 1,
                'resultsPerPage' => 50,
                'totalPages' => 1,
                'totalCommits' => 1,
            ]);

        $this->commitFactory->method('make')->willReturn($mockService);

        ob_start();
        $controller = new VersionHistoryController($this->commitFactory);
        $controller->view();
        $output = ob_get_clean();

        $this->assertStringContainsString('Kurt Cobain', $output);
        $this->assertStringContainsString('Smells Like Teen Spirit', $output);
    }

    public function testViewHandlesServiceException(): void
    {
        $_GET['page'] = 1;
        $_GET['results_per_page'] = 100;

        $mockService = $this->createMock(AbstractCommitService::class);
        $mockService->method('viewCommits')
            ->willThrowException(new CommitServiceException('Test error'));

        $this->commitFactory->method('make')->willReturn($mockService);

        ob_start();
        $controller = new VersionHistoryController($this->commitFactory);
        $controller->view();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test error', $output);
    }

    public function testGetFetchesAndSavesCommits(): void
    {
        $_GET['commit_count'] = 200;

        $mockService = $this->createMock(AbstractCommitService::class);
        $mockService->expects($this->once())
            ->method('getCommits')
            ->with($this->isInstanceOf(GetParamsDTO::class));

        $this->commitFactory->method('make')->willReturn($mockService);

        ob_start();
        $controller = new VersionHistoryController($this->commitFactory);
        $controller->get();
        $output = ob_get_clean();

        $this->assertStringContainsString('Commits fetched and stored successfully.', $output);
    }

    public function testGetHandlesCommitServiceException(): void
    {
        $_GET['commit_count'] = 100;

        $mockService = $this->createMock(AbstractCommitService::class);
        $mockService->method('getCommits')
            ->willThrowException(new CommitServiceException('fetch failed'));

        $this->commitFactory->method('make')->willReturn($mockService);

        ob_start();
        $controller = new VersionHistoryController($this->commitFactory);
        $controller->get();
        $output = ob_get_clean();

        $this->assertStringContainsString('fetch failed', $output);
    }
}
