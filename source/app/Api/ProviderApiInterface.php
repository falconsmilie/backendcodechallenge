<?php

namespace App\Api;

use App\Exceptions\VersionControlApiException;

interface ProviderApiInterface
{
    /**
     * @throws VersionControlApiException
     */
    public function mostRecentCommits(
        string $owner,
        string $repo,
        int $page = 1,
        int $perPage = 100,
    ): array;
}