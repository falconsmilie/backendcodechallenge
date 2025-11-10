<?php

namespace App\Contracts;

use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\DataTransferObjects\ViewDTO;

interface CommitServiceInterface
{
    public function viewCommits(PaginationDTO $pagination): ViewDTO;

    public function getCommits(GetParamsDTO $params): void;
}
