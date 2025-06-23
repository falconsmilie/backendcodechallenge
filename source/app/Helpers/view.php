<?php

if (!function_exists('view')) {

    function view(string $template, array $data = []): void
    {
        extract($data);

        ob_start();
        require __DIR__ . "/../Views/{$template}.php";
        $content = ob_get_clean();

        require __DIR__ . "/../Views/layout.php";
    }
}
