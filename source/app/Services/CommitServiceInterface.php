<?php

namespace App\Services;

use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;

interface CommitServiceInterface
{
    public function viewCommits(PaginationDTO $pagination): array;

    public function getCommits(GetParamsDTO $params): bool;
}
