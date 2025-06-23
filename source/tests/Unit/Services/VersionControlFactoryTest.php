<?php

namespace Tests\Unit\Services;

use App\Services\GitHub\GitHubService;
use App\Services\CommitFactory;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VersionControlFactoryTest extends TestCase
{
    #[Test]
    public function testReturnsGithubServiceInstance(): void
    {
        $factory = new CommitFactory('github', 'test-owner', 'test-repo');
        $service = $factory->make();

        $this->assertInstanceOf(GitHubService::class, $service);
    }

    public static function unsupportedProviderNames(): array
    {
        return [
            ['gitlab'],
            ['bitbucket'],
            ['azure'],
            [''],
        ];
    }

    #[Test]
    #[DataProvider('unsupportedProviderNames')]
    public function testThrowsExceptionForUnsupportedProviders(string $provider): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("$provider is not currently supported.");

        $factory = new CommitFactory($provider, 'test-owner', 'test-repo');
        $factory->make();
    }
}
