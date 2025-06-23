<?php

namespace App\Contracts;

interface CommitGetInterface
{
    public function mostRecentCommits(int $pages, int $perPage, callable $processCommit): bool;
}
