<?php

return [
    'github' => [
        'api_base_uri' => 'https://api.github.com/',
        'headers' => [
            'user_agent' => 'CommitFetcherApp',
            'accept' => 'application/vnd.github.v3+json',
        ],
        'requests' => [
            'fetch_per_page_limit' => 100,
        ],
    ],
];
