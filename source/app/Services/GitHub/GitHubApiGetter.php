<?php
namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Contracts\CommitGetInterface;
use App\DataTransferObjects\CommitDTO;
use App\Exceptions\VersionControlApiException;
use DateTimeImmutable;
use DateTimeZone;

readonly class GitHubApiGetter implements CommitGetInterface
{
    public function __construct(
        private GitHubApi $api,
        private string $owner,
        private string $repo,
    ) {}

    /**
     * @throws VersionControlApiException
     */
    public function mostRecentCommits(int $pages, int $perPage, callable $processCommit): bool
    {
        for ($page = 1; $page <= $pages; $page++) {

            $commitCount = 0;
            $commits = $this->api->mostRecentCommits($this->owner, $this->repo, $page, $perPage);

            if (empty($commits)) {
                break;
            }

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commit = $this->format($commit);
                    $processCommit($commit);
                    $commitCount++;
                }
            }

            if ($commitCount < $perPage) {
                break;
            }
        }

        return true;
    }

    private function format(array $commit): CommitDTO
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return new CommitDTO(
            provider: 'github',
            owner: $this->owner,
            repo: $this->repo,
            hash: $commit['sha'],
            author: $commit['commit']['author']['name'] ?? 'Unknown',
            authorAvatarUrl: $commit['author']['avatar_url'] ?? '',
            authorHtmlUrl: $commit['author']['html_url'] ?? '',
            commitDate: new DateTimeImmutable($commit['commit']['author']['date'])->format('Y-m-d H:i:s'),
            commitMessage: $commit['commit']['message'],
            commitHtmlUrl: $commit['html_url'],
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
