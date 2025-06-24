<?php
namespace App\Controllers;

use App\Exceptions\CommitServiceException;
use App\Services\CommitFactory;
use Exception;

class VersionHistoryController
{
    private const int PAGE_LIMIT = 1;
    private const int RESULTS_PER_PAGE = 100;
    private const int GET_COMMIT_COUNT = 1000;

    public function __construct(protected string $provider, protected string $owner, protected string $repo)
    {
    }

    public function index(): void
    {
        view('index');
    }

    public function view(): void
    {
        // imagine we do some validation here wth these query params ...
        $page = isset($_GET['page'])
            ? max(self::PAGE_LIMIT, (int)$_GET['page'])
            : self::PAGE_LIMIT;

        $resultsPerPage = isset($_GET['results_per_page'])
            ? min(self::RESULTS_PER_PAGE, (int)$_GET['results_per_page'])
            : self::RESULTS_PER_PAGE;

        view(
            'view-commits',
            new CommitFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->viewCommits($page, $resultsPerPage)
        );
    }

    public function get(): void
    {
        // imagine we do some validation here wth these query params ...
        $count = isset($_GET['commit_count'])
            ? max(self::GET_COMMIT_COUNT, (int)$_GET['commit_count'])
            : self::GET_COMMIT_COUNT;

        try {
            new CommitFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->getCommits($count);
        } catch (CommitServiceException $e) {
            // TODO: create an acceptable user error message, instead of exposing our internals
            view('fetch-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('get-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
