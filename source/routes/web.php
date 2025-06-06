<?php

use App\Controllers\VersionHistoryController;

require_once __DIR__ . '/../bootstrap/bootstrap.php';

// this is really hacky, most frameworks are going to come with routing

$uri = trim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
);

$segments = explode('/', $uri);

$action = $segments[0] ?? '';
$provider = '';
$owner = '';
$repo = '';

if (count($segments) === 4) {
    [$provider, $owner, $repo] = array_slice($segments, 1, 3);
} elseif (count($segments) === 2) {
    $provider = $segments[1];
} else {
    $action = 'index';
}

$controller = new VersionHistoryController($provider, $owner, $repo);

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'get':
        $controller->get();
        break;
    case 'view':
        $controller->view();
        break;
    default:
        http_response_code(404);
        echo "404 - Page Not Found";
}
