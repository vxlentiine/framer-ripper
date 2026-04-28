<?php
require __DIR__ . '/config.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
} elseif ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
} else {
    require __DIR__ . '/proxy.php';
}
