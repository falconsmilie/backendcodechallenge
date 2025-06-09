<?php

namespace App\Contracts;

interface CommitSaveInterface
{
    public function saveMany(array $commits): void;
}