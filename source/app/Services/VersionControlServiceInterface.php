<?php

namespace App\Services;

interface VersionControlServiceInterface
{
    public function view(int $page = 1, int $resultsPerPage = 100): array;

    public function get(int $count = 1000): bool;
}