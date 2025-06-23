<?php

namespace App\Services\Commit;

use App\Contracts\CommitSaveInterface;
use App\DataTransferObjects\CommitDTO;

final class BufferedCommitSaver
{
    /** @var array<int, array<string, mixed>> */
    private array $buffer = [];

    public function __construct(
        private readonly CommitSaveInterface $repository,
        private readonly int $bufferSize
    ) {
    }

    public function __invoke(CommitDTO $dto): void
    {
        $this->buffer[] = $dto->toArray();

        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (!empty($this->buffer)) {
            $this->repository->saveMany($this->buffer);
            $this->buffer = [];
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}
