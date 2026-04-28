<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: /\n\nSitemap: {$myDomain}/sitemap.xml\n";
