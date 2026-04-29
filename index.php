<?php
require __DIR__ . '/config.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
} elseif ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
} elseif (strpos($path, '/assets/') === 0 || strpos($path, '/fstatic/') === 0) {
    require __DIR__ . '/asset.php';
} else {
    require __DIR__ . '/proxy.php';
}
