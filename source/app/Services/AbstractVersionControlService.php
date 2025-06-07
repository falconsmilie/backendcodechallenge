<?php

namespace App\Services;

use App\Exceptions\VersionControlException;

abstract class AbstractVersionControlService
{
    abstract public function getVersionControlService(): VersionControlConnectorInterface;

    /**
     * @throws VersionControlException
     */
    public function get(int $count = 100): array
    {
        return $this->getVersionControlService()
            ->get($count);
    }

    public function view(int $resultsPerPage = 100): array
    {
        return $this->getVersionControlService()
            ->view($resultsPerPage);
    }
}