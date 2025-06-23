<?php

namespace App\Services;

interface CommitServiceInterface
{
    public function viewCommits(int $page = 1, int $resultsPerPage = 100): array;

    public function getCommits(int $count = 1000): bool;
}