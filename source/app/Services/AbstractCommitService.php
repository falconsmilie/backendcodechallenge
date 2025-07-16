<?php

namespace App\Services;

use App\Contracts\CommitServiceInterface;
use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\Exceptions\CommitServiceException;

abstract class AbstractCommitService implements CommitServiceInterface
{
    /**
     * @throws CommitServiceException
     */
    abstract public function getCommits(GetParamsDTO $params): void;

    abstract public function viewCommits(PaginationDTO $pagination): array;
}
