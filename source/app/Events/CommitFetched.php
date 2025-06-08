<?php

namespace App\Events;

final class CommitFetched
{
    public function __construct(public array $commit)
    {
    }
}
