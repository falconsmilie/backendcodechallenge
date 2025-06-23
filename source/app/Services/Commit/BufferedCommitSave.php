<?php

namespace App\Services\Commit;

use App\Contracts\CommitSaveInterface;
use App\DataTransferObjects\CommitDTO;

final class BufferedCommitSave
{
    private const int MAX_BUFFER_SIZE = 500;
    private array $buffer = [];

    public function __construct(
        private readonly CommitSaveInterface $repository,
        private int $bufferSize
    ) {
        $this->bufferSize = min(self::MAX_BUFFER_SIZE, $bufferSize);
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
