<?php
namespace Tests\Doubles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuilderFake extends Builder
{
    private int $insertCalls = 0;

    private Collection $fakeCollection;

    public function __construct()
    {
        // We don’t need the parent for tests
    }

    public function insertOrIgnore(array $values): true
    {
        $this->insertCalls++;
        return true;
    }

    public function getInsertCalls(): int
    {
        return $this->insertCalls;
    }

    public function count($columns = '*'): int
    {
        return 42;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and'): BuilderFake|static
    {
        return $this;
    }

    public function orderBy($column, $direction = 'asc'): BuilderFake|static
    {
        return $this;
    }

    public function skip($value): BuilderFake|static
    {
        return $this;
    }

    public function take($value): BuilderFake|static
    {
        return $this;
    }

    public function get($columns = ['*']): \Illuminate\Database\Eloquent\Collection|Collection
    {
        return collect();
    }
}
