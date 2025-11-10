<?php
namespace App\Controllers;

use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
use App\DataTransferObjects\ViewDTO;
use App\Exceptions\CommitServiceException;
use App\Factories\CommitFactory;
use InvalidArgumentException;
use function view;

class VersionHistoryController
{
    private const int DEFAULT_PAGE = 1;
    private const int RESULTS_PER_PAGE = 100;
    private const int GET_COMMIT_COUNT = 1000;

    public function __construct(protected CommitFactory $commitFactory)
    {
    }

    public function index(): void
    {
        view('index');
    }

    public function view(): void
    {
        $page = isset($_GET['page'])
            ? max(self::DEFAULT_PAGE, (int)$_GET['page'])
            : self::DEFAULT_PAGE;

        $resultsPerPage = isset($_GET['results_per_page'])
            ? min(self::RESULTS_PER_PAGE, (int)$_GET['results_per_page'])
            : self::RESULTS_PER_PAGE;

        $pagination = new PaginationDTO($page, $resultsPerPage);

        try {
            $pageData = $this->commitFactory
                ->make()
                ->viewCommits($pagination);
        } catch (CommitServiceException | InvalidArgumentException $e) {
            $pageData = ViewDTO::emptyWithError($e->getMessage(), $page, $resultsPerPage);
        }

        view('view-commits', $pageData->toArray());
    }

    public function get(): void
    {
        $count = isset($_GET['commit_count'])
            ? min(self::GET_COMMIT_COUNT, (int)$_GET['commit_count'])
            : self::GET_COMMIT_COUNT;

        $getParams = new GetParamsDTO($count);

        try {
            $this->commitFactory->make()->getCommits($getParams);
        } catch (CommitServiceException $e) {
            view('get-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('get-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
