<?php
namespace App\Repositories;

use App\Models\Commit;
use Illuminate\Database\Eloquent\Builder;

readonly class MySqlCommitRepository implements CommitRepositoryInterface
{
    public function __construct(private Commit $commit) {}

    public function saveMany(array $commits): void
    {
        collect($commits)
            ->chunk(500)
            ->each(function ($chunk) {
                $this->commit->newQuery()->insertOrIgnore($chunk->toArray());
            });
    }

    public function getByProviderGroupedByAuthor(
        int $offset,
        int $limit,
        string $provider,
        ?string $owner = null,
        ?string $repo = null,
    ): array {
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
