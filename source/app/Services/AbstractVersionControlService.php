<?php

namespace App\Services;

use App\Exceptions\VersionControlServiceException;

abstract class AbstractVersionControlService implements VersionControlServiceInterface
{
    abstract public function getVersionControlService(): VersionControlServiceInterface;

    /**
     * @throws VersionControlServiceException
     */
    public function get(int $count = 100): array
    {
        return $this->getVersionControlService()
            ->get($count);
    }

    public function view(int $page = 1, int $resultsPerPage = 100): array
    {
        return $this->getVersionControlService()
            ->view($page, $resultsPerPage);
    }
}