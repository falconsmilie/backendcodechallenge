<?php
namespace App\Controllers;

use App\Exceptions\VersionControlServiceException;
use App\Services\VersionControlFactory;
use Exception;

class VersionHistoryController
{
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
        // imagine we do some validation here ...
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        view(
            'view-commits',
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->view($page, 20)
        );
    }

    public function get(): void
    {
        try {
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->get(1000);

        } catch (VersionControlServiceException | Exception $e) {
            // TODO: create an acceptable user error message, instead of exposing our internals
            view('fetch-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('get-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
