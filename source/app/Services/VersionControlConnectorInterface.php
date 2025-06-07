<?php

namespace App\Services;

interface VersionControlConnectorInterface
{
    public function view(int $resultsPerPage = 100): array;

    public function get(int $count = 1000): array;
}