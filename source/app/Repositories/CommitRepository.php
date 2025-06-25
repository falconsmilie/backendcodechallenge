<?php
namespace App\Repositories;

use App\Api\GitHub\GitHubApi;
use App\Api\ProviderApiInterface;
use App\Contracts\CommitFormatInterface;
use App\Contracts\CommitGetInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\DataTransferObjects\CommitDTO;
use App\Exceptions\CommitApiException;
use App\Exceptions\CommitRepositoryException;
use App\Models\Commit;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class CommitRepository implements CommitSaveInterface, CommitViewInterface, CommitGetInterface, CommitFormatInterface
{
    public function __construct(private readonly Commit $commit, private ?ProviderApiInterface $api = null)
    {
        $this->api = $api ?? new GitHubApi();
    }

    /**
     * @throws CommitRepositoryException
     */
    public function saveMany(array $commits): void
    {
        try {
            $this->commit->newQuery()->upsert($commits, ['hash']);
        } catch (Exception $e) {
            throw new CommitRepositoryException($e->getMessage(), (int)$e->getCode(), $e);
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

    /**
     * @throws CommitRepositoryException
     */
    public function mostRecentCommits(
        string $provider,
        string $owner,
        string $repo,
        int $pages,
        int $perPage,
        callable $processCommit
    ): bool {

        for ($page = 1; $page <= $pages; $page++) {
            $commitCount = 0;

            try {
                $result = $this->api->mostRecentCommits($owner, $repo, $page, $perPage);
                $commits = $result['commits'];
                $hasNextPage = $result['hasNextPage'];
            } catch (CommitApiException $e) {
                // TODO: let's just go to the next page for now ...
                continue;
            }

            if (empty($commits)) {
                break;
            }

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commit = $this->format($commit, $provider, $owner, $repo);
                    $processCommit($commit);
                    $commitCount++;
                }
            }

            if ($commitCount < $perPage || !$hasNextPage) {
                break;
            }
        }

        return true;
    }

    /**
     * @throws CommitRepositoryException
     */
    public function format(array $rawCommit, $provider, string $owner, string $repo): CommitDTO
    {
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $commitDate = new DateTimeImmutable($rawCommit['commit']['author']['date']);
        } catch (Exception $e) {
            throw new CommitRepositoryException($e->getMessage(), $e->getCode(), $e);
        }

        return new CommitDTO(
            provider: $provider,
            owner: $owner,
            repo: $repo,
            hash: $rawCommit['sha'],
            author: $rawCommit['commit']['author']['name'] ?? 'Unknown',
            authorAvatarUrl: $rawCommit['author']['avatar_url'] ?? null,
            authorHtmlUrl: $rawCommit['author']['html_url'] ?? null,
            commitDate: $commitDate,
            commitMessage: $rawCommit['commit']['message'] ?? null,
            commitHtmlUrl: $rawCommit['html_url'] ?? null,
            createdAt: $now,
            updatedAt: $now,
        );
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
