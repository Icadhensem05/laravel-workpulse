<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicPath = __DIR__ . '/../public';
$requested = realpath($publicPath . $uri);

if ($uri !== '/' && $requested && str_starts_with($requested, realpath($publicPath)) && is_file($requested)) {
    return false;
}

require $publicPath . '/index.php';
