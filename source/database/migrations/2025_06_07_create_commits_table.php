<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// ⚠️ DANGEROUS in production — ensure you're not losing data
Capsule::schema()->dropIfExists('commits');

Capsule::schema()->create('commits', function (Blueprint $table): void {

    echo "Running create 'commits' ... \n";

    $table->id();

    $table->string('provider');
    $table->string('owner');
    $table->string('repo');

    $table->string('hash', 40)->unique(); // Git SHA-1 hash
    $table->string('author');
    $table->string('author_avatar_url');
    $table->string('author_html_url');

    $table->timestamp('commit_date');
    $table->text('commit_message');
    $table->string('commit_html_url');

    $table->timestamps();

    echo "Adding Indexes to 'commits' ... \n";
    $table->index(['provider', 'owner', 'repo'], 'idx_provider_owner_repo');
    $table->index('author', 'idx_author');
    $table->index('commit_date', 'idx_commit_date');
});

echo "Table 'commits' created.\n";
