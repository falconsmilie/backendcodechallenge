<?php

namespace Tests\Unit\Services;

use App\Exceptions\CommitServiceException;
use App\Services\CommitFactory;
use App\Services\GitHub\GitHubService;
use PHPUnit\Framework\TestCase;

final class CommitFactoryTest extends TestCase
{
    public function testCreatesGitHubServiceWhenProviderIsSupported(): void
    {
        $factory = new CommitFactory('github', 'soundgarden', 'superunknown');
        $service = $factory->make();

        $this->assertInstanceOf(GitHubService::class, $service);
    }

    public function testProviderNameIsCaseInsensitive(): void
    {
        $factory = new CommitFactory('GITHUB', 'nirvana', 'nevermind');
        $service = $factory->make();

        $this->assertInstanceOf(GitHubService::class, $service);
    }

    public function testThrowsExceptionForUnsupportedProvider(): void
    {
        $this->expectException(CommitServiceException::class);
        $this->expectExceptionMessage('subpop is not currently supported.');

        $factory = new CommitFactory('subpop', 'pearljam', 'ten');
        $factory->make();
    }
}
