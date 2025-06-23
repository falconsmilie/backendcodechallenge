<?php

namespace App\DataTransferObjects;

final readonly class CommitDTO
{
    public function __construct(
        private string $provider,
        private string $owner,
        private string $repo,
        private string $hash,
        private string $author,
        private ?string $authorAvatarUrl,
        private ?string $authorHtmlUrl,
        private string $commitDate,
        private ?string $commitMessage,
        private ?string $commitHtmlUrl,
        private string $createdAt,
        private string $updatedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'owner' => $this->owner,
            'repo' => $this->repo,
            'hash' => $this->hash,
            'author' => $this->author,
            'author_avatar_url' => $this->authorAvatarUrl,
            'author_html_url' => $this->authorHtmlUrl,
            'commit_date' => $this->commitDate,
            'commit_message' => $this->commitMessage,
            'commit_html_url' => $this->commitHtmlUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
