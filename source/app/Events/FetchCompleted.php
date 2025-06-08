<?php

namespace App\Events;

final class FetchCompleted
{
    public function __construct(public array $commits)
    {
    }
}
