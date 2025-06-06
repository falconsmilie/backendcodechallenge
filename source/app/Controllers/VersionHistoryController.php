<?php
namespace App\Controllers;

use App\Exceptions\VersionControlException;
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
        view(
            'view-commits',
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->view()
        );
    }

    public function get(): void
    {
        try {
            new VersionControlFactory($this->provider, $this->owner, $this->repo)
                ->make()
                ->get(10);

        } catch (VersionControlException | Exception $e) {
            // TODO: create an acceptable user error message, instead of exposing our internals
            view('fetch-commits', ['message' => $e->getMessage()]);

            return;
        }

        view('fetch-commits', ['message' => 'Commits fetched and stored successfully.']);
    }
}
