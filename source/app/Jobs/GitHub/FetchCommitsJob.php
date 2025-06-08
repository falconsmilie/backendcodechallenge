<?php

namespace App\Jobs\GitHub;

use App\Exceptions\VersionControlException;
use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

readonly class FetchCommitsJob
{
    public function __construct(private Client $client, private int $count = 100)
    {
    }

    /**
     * @throws VersionControlException
     */
    public function handle(string $provider, string $owner, string $repo, int $pageLimit = 100): array
    {
        $perPage = min($this->count, $pageLimit);
        $pages = (int) ceil($this->count / $perPage);

        $commitHashes = [];

        for ($page = 1; $page <= $pages; $page++) {

            $commits = $this->query($page, $perPage, $owner, $repo);

            foreach ($commits as $commit) {
                if (isset($commit['sha'])) {
                    $formatted = $this->formatCommit($provider, $owner, $repo, $commit);
                    $commitHashes[] = $formatted;

//                    $this->events->dispatch(new CommitFetched($formatted));
                }
            }

            if (count($commitHashes) < $perPage) {
                break;
            }
        }

        new MySqlCommitRepository(new Commit())->saveMany($commitHashes);

//        $this->events->dispatch(new FetchCompleted($commitHashes));

        return $commitHashes;
    }

    /**
     * @throws VersionControlException
     */
    protected function query(int $page, int $perPage, string $owner, string $repo): array
    {
        try {
            $response = $this->client->get('repos/'.$owner.'/'.$repo.'/commits', [
                'query' => [
                    'per_page' => $perPage,
                    'page' => $page,
                ],
            ]);
        } catch (GuzzleException $e) {
            // TODO: log the errors
            throw new VersionControlException($e->getMessage(), $e->getCode(), $e);
        }

        $commits = json_decode($response->getBody()->getContents(), true);

        if ($commits === null) {
            throw new VersionControlException(
                'Something went wrong reading the '.$owner.'/'.$repo .' repo.'
            );
        }

        return $commits;
    }

    protected function formatCommit(string $provider, string $owner, string $repo, array $commit): array
    {
        return [
            'provider' => $provider,
            'owner' => $owner,
            'repo' => $repo,
            'hash' => $commit['sha'],
            'author' => $commit['commit']['author']['name'] ?? 'Unknown',
            'author_avatar_url' => $commit['author']['avatar_url'] ?? '',
            'author_html_url' => $commit['author']['html_url'] ?? '',
            'commit_date' => $commit['commit']['author']['date'],
            'commit_message' => $commit['commit']['message'],
            'commit_html_url' => $commit['html_url'],
        ];
    }
}
