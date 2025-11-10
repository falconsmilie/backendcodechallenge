<?php

namespace App\DataTransferObjects;

final readonly class ViewDTO
{
    public function __construct(
        public ?string $error,
        public array $commits,
        public int $page,
        public int $resultsPerPage,
        public int $totalPages,
        public int $totalCommits,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'commits' => $this->commits,
            'page' => $this->page,
            'resultsPerPage' => $this->resultsPerPage,
            'totalPages' => $this->totalPages,
            'totalCommits' => $this->totalCommits,
        ];
    }

    public static function emptyWithError(string $error, int $page, int $resultsPerPage): self
    {
        return new self($error, [], $page, $resultsPerPage, 0, 0);
    }
}
