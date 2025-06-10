<?php
namespace App\Repositories;

use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\Exceptions\VersionControlRepositoryException;
use App\Models\Commit;
use Illuminate\Database\Eloquent\Builder;

class MySqlCommitRepository implements CommitSaveInterface, CommitViewInterface
{
    private const int SAVE_MANY_CHUNK_SIZE = 500;

    public function __construct(private readonly Commit $commit) {}

    public function saveMany(array $commits): void
    {
        collect($commits)
            ->chunk(self::SAVE_MANY_CHUNK_SIZE)
            ->each(function ($chunk) {
                $this->commit->newQuery()->insertOrIgnore($chunk->toArray());
            });
    }

    /**
     * @throws VersionControlRepositoryException
     */
    public function getByProviderGroupedByAuthor(
        int $offset,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null,
    ): array {

        if ($offset < 1 || $limit < 1) {
            throw new VersionControlRepositoryException('Offset and limit must be greater than 0.');
        }

        $query = $this->commit->newQuery()
            ->where('provider', $provider);

        $this->applyOwnerRepoFilters($query, $owner, $repo);

        return $query->orderBy('commit_date', 'desc')
            ->skip(($offset - 1) * $limit)
            ->take($limit)
            ->get()
            ->groupBy('author')
            ->toArray();
    }

    public function countByProvider(string $provider, ?string $owner = null, ?string $repo = null): int
    {
        $query = $this->commit->newQuery()
            ->where('provider', $provider);

        $this->applyOwnerRepoFilters($query, $owner, $repo);

        return $query->count();
    }

    private function applyOwnerRepoFilters(Builder $query, ?string $owner, ?string $repo): void
    {
        if ($owner) {
            $query->where('owner', $owner);
        }

        if ($repo) {
            $query->where('repo', $repo);
        }
    }
}
