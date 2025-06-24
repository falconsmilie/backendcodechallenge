<?php
namespace App\Contracts;

interface CommitViewInterface
{
    public function countByProvider(string $provider, string $owner, string $repo): int;

    public function getByProviderGroupedByAuthor(
        int $page,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null
    ): array;
}
