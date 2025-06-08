<?php

namespace App\Api;

interface ProviderApiInterface
{
    public function getRecentCommits(
        string $owner,
        string $repo,
        int $count = 100,
        int $page = 1,
        int $perPage = 100,
    ): array;
}