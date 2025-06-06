<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\GitHubService;
use App\Services\VersionControlConnector;

class GitHubServiceTest extends TestCase
{
    public function testGetVersionControlServiceReturnsConnector(): void
    {
        $service = new GitHubService('owner', 'repo');

        $this->assertInstanceOf(
            VersionControlConnector::class,
            $service->getVersionControlService()
        );
    }
}
