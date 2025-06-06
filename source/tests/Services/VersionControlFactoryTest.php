<?php

namespace Tests\Services;

use App\Services\VersionControlFactory;
use App\Services\GitHubService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Exception;

class VersionControlFactoryTest extends TestCase
{
    #[Test]
    public function test_it_returns_github_service_instance(): void
    {
        $factory = new VersionControlFactory('github', 'test-owner', 'test-repo');
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
    public function test_it_throws_exception_for_unsupported_providers(string $provider): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("$provider is not currently supported.");

        $factory = new VersionControlFactory($provider, 'test-owner', 'test-repo');
        $factory->make();
    }
}
