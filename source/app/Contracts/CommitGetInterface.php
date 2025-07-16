<?php

namespace App\Contracts;

interface CommitGetInterface
{
    public function mostRecentCommits(
        string $provider,
        string $owner,
        string $repo,
        int $pages,
        int $perPage,
        callable $processCommit
    ): void;
}
