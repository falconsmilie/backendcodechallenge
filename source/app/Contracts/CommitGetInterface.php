<?php

namespace App\Contracts;

interface CommitGetInterface
{
    public function mostRecentCommits(int $count, int $pages, int $perPage): array;
}
