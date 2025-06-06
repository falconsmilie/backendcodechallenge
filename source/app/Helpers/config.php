<?php

if (!function_exists('config')) {

    function config(string $key, $default = null)
    {
        $config = [];

        foreach (glob(__DIR__ . '/../../config/*.php') as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
