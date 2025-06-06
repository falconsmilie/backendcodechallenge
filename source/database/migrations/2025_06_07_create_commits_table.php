<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (! Capsule::schema()->hasTable('commits')) {

    Capsule::schema()->create('commits', function ($table) {
        $table->id();
        $table->string('provider');
        $table->string('owner');
        $table->string('repo');
        $table->string('hash')->unique();
        $table->string('author');
        $table->string('author_avatar_url');
        $table->string('author_html_url');
        $table->string('commit_date');
        $table->text('commit_message');
        $table->string('commit_html_url');
        $table->timestamps();
    });

    echo "Table 'commits' created.\n";

} else {
    echo "Table 'commits' already exists. Skipping migration.\n";
    exit;
}