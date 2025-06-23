<?php
namespace App\Controllers;

use App\Exceptions\VersionControlServiceException;
use App\Services\VersionControlFactory;
use Exception;

class VersionHistoryController
{
    private const int PAGE = 1;
    private const int RESULTS_PER_PAGE = 12;
    private const int GET_COMMIT_COUNT = 1000;

    public function __construct(protected string $provider, protected string $owner, protected string $repo)
    {
    }

    public function index(): void
    {
        view('index');
    }

    /**
     * @throws Exception
     */
    public function view(): void
    {
        // imagine we do some validation here wth these query params ...
        $page = isset($_GET['page'])
            ? max(1, (int)$_GET['page'])
            : self::PAGE;

        $resultsPerPage = isset($_GET['results_per_page'])
            ? max(1, (int)$_GET['results_per_page'])
            : self::RESULTS_PER_PAGE;

        view(
            'view-commits',
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->view($page, $resultsPerPage)
        );
    }

    public function get(): void
    {
        // imagine we do some validation here wth these query params ...
        $count = isset($_GET['commit_count'])
            ? max(1, (int)$_GET['commit_count'])
            : self::GET_COMMIT_COUNT;

        try {
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->get($count);

        } catch (VersionControlServiceException | Exception $e) {
            // TODO: create an acceptable user error message, instead of exposing our internals
            view('fetch-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('get-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
