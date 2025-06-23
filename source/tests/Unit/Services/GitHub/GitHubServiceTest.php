<?php

namespace Tests\Unit\Services\GitHub;

use App\Services\GitHub\GitHubService;
use App\Services\CommitServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitHubServiceTest extends TestCase
{
    #[Test]
    public function testGetVersionControlServiceReturnsConnector(): void
    {
        $service = new GitHubService('owner', 'repo');

        $this->assertInstanceOf(
            CommitServiceInterface::class,
            $service->getVersionControlService()
        );
    }
}
