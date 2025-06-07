<?php

namespace App\Repositories;

interface CommitRepositoryInterface
{
    public function saveMany(array $commits): void;

    public function getByProviderGroupedByAuthor(
        int $offset,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null,
    ): array;

    public function countByProvider(string $provider, ?string $owner = null, ?string $repo = null): int;
}