<?php

namespace App\Services;

use App\Exceptions\CommitServiceException;

abstract class AbstractCommitService implements CommitServiceInterface
{
    /**
     * @throws CommitServiceException
     */
    abstract public function getCommits(int $count = 100): bool;

    abstract public function viewCommits(int $page = 1, int $resultsPerPage = 100): array;
}
