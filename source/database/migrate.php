<?php

require_once __DIR__ . '/../bootstrap/bootstrap.php';

foreach (glob(__DIR__ . '/migrations/*.php') as $file) {
    require_once $file;
}

echo "Migrations executed.\n";