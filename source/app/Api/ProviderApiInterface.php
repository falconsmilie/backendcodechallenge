<?php

namespace App\Api;

use App\Exceptions\CommitApiException;

interface ProviderApiInterface
{
    /**
     * @throws CommitApiException
     */
    public function mostRecentCommits(
        string $owner,
        string $repo,
        int $page = 1,
        int $perPage = 100,
    ): array;
}
