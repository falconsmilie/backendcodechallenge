<?php
namespace App\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Contracts\CommitGetInterface;
use App\Exceptions\VersionControlApiException;

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
    public function mostRecentCommits(int $count, int $pages, int $perPage): array
    {
        $commitHashes = [];

        for ($page = 1; $page <= $pages; $page++) {

            $commits = $this->api->mostRecentCommits($this->owner, $this->repo, $count, $page, $perPage);

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $commitHashes[] = $commit;
                }
            }

            if (count($commitHashes) < $perPage) {
                break;
            }
        }

        return $commitHashes;
    }
}
