<?php
namespace App\Controllers;

use App\DataTransferObjects\GetParamsDTO;
use App\DataTransferObjects\PaginationDTO;
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
            $data = $this->commitFactory->make()->viewCommits($pagination);
            $pageData = [
                'error' => null,
                'commits' => $data['commits'],
                'page' => $data['page'],
                'resultsPerPage' => $data['resultsPerPage'],
                'totalPages' => $data['totalPages'],
                'totalCommits' => $data['totalCommits'],
            ];
        } catch (CommitServiceException | InvalidArgumentException $e) {
            $pageData = [
                'error' => $e->getMessage(),
                'commits' => [],
                'page' => $page,
                'resultsPerPage' => $resultsPerPage,
                'totalPages' => 0,
                'totalCommits' => 0
            ];
        }

        view('view-commits', $pageData);
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
