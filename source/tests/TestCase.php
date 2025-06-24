<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->createCommitsTable();
    }

    private function createCommitsTable(): void
    {
        DB::schema()->create('commits', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('owner');
            $table->string('repo');
            $table->string('hash')->unique();
            $table->string('author');
            $table->string('author_avatar_url')->nullable();
            $table->string('author_html_url')->nullable();
            $table->timestamp('commit_date');
            $table->text('commit_message')->nullable();
            $table->string('commit_html_url')->nullable();
            $table->timestamps();
        });
    }
}
