<?php
namespace App\Repositories;

use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\Exceptions\CommitRepositoryException;
use App\Models\Commit;
use Exception;
use Illuminate\Database\Eloquent\Builder;

readonly class MySqlCommitRepository implements CommitSaveInterface, CommitViewInterface
{
    public function __construct(private Commit $commit)
    {
    }

    /**
     * @throws CommitRepositoryException
     */
    public function saveMany(array $commits): void
    {
        try {
            $this->commit->newQuery()->upsert($commits, ['hash']);
        } catch (Exception $e) {
            throw new CommitRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws CommitRepositoryException
     */
    public function getByProviderGroupedByAuthor(
        int $page,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null,
    ): array {

        if ($page < 1 || $limit < 1) {
            throw new CommitRepositoryException('Offset and limit must be greater than 0.');
        }

        $query = $this->commit->newQuery()->where('provider', $provider);

        $this->applyOwnerRepoFilters($query, $owner, $repo);

        return $query->orderBy('commit_date', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->groupBy('author')
            ->toArray();
    }

    public function countByProvider(string $provider, ?string $owner = null, ?string $repo = null): int
    {
        $query = $this->commit->newQuery()->where('provider', $provider);

        $this->applyOwnerRepoFilters($query, $owner, $repo);

        return $query->count();
    }

    private function applyOwnerRepoFilters(Builder $query, ?string $owner, ?string $repo): void
    {
        if ($owner !== null && $owner !== '') {
            $query->where('owner', $owner);
        }

        if ($repo !== null && $repo !== '') {
            $query->where('repo', $repo);
        }
    }
}
