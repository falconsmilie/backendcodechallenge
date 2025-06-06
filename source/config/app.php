<?php

return [
    'pagination' => [
        'commits_per_page' => 50,
    ],

    'github' => [
        'base_uri' => 'https://api.github.com/',
        'headers' => [
            'user_agent' => 'CommitFetcherApp',
            'accept' => 'application/vnd.github.v3+json',
        ],
        'repo_owner' => 'nodejs',
        'repo_name' => 'node',
    ]
];