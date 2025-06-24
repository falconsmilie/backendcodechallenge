<?php
namespace App\Controllers;

use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\Exceptions\CommitServiceException;
use App\Services\CommitFactory;
use InvalidArgumentException;

class VersionHistoryController
{
    private const int DEFAULT_PAGE = 1;
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
            ? max(self::DEFAULT_PAGE, (int)$_GET['page'])
            : self::DEFAULT_PAGE;

        $resultsPerPage = isset($_GET['results_per_page'])
            ? min(self::RESULTS_PER_PAGE, (int)$_GET['results_per_page'])
            : self::RESULTS_PER_PAGE;

        $pagination = new PaginationDTO($page, $resultsPerPage);

        try {
            view(
                'view-commits',
                new CommitFactory($this->provider, $this->owner, $this->repo)
                    ->make()
                    ->viewCommits($pagination)
            );
        } catch (CommitServiceException | InvalidArgumentException $e) {
            view(
                'view-commits',
                [
                    'error' => $e->getMessage(),
                    'commits' => [],
                    'page' => $page,
                    'resultsPerPage' => $resultsPerPage,
                    'totalPages' => 0,
                    'totalCommits' => 0
                ]
            );
        }
    }

    public function get(): void
    {
        // imagine we do some validation here wth these query params ...
        $count = isset($_GET['commit_count'])
            ? min(self::GET_COMMIT_COUNT, (int)$_GET['commit_count'])
            : self::GET_COMMIT_COUNT;

        $getParams = new GetParamsDTO($count);

        try {
            new CommitFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->getCommits($getParams);
        } catch (CommitServiceException $e) {
            // TODO: create an acceptable user error message, instead of exposing our internals
            view('fetch-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('get-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
