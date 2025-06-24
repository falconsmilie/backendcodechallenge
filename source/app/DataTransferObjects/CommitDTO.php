<?php

namespace App\DataTransferObjects;

use DateTimeImmutable;

final readonly class CommitDTO
{
    public function __construct(
        public string $provider,
        public string $owner,
        public string $repo,
        public string $hash,
        public string $author,
        public ?string $authorAvatarUrl,
        public ?string $authorHtmlUrl,
        public DateTimeImmutable $commitDate,
        public ?string $commitMessage,
        public ?string $commitHtmlUrl,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
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
            'commit_date' => $this->commitDate->format('Y-m-d H:i:s'),
            'commit_message' => $this->commitMessage,
            'commit_html_url' => $this->commitHtmlUrl,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
