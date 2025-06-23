<?php
namespace App\Contracts;

use App\DataTransferObjects\CommitDTO;

interface CommitFormatInterface
{
    public function format(array $rawCommit, string $provider, string $owner, string $repo): CommitDTO;
}
