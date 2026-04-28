<?php
require __DIR__ . '/config.php';

$cacheFile = __DIR__ . '/cache/sitemap.xml';

if (!$devMode && file_exists($cacheFile)) {
    header('Content-Type: application/xml; charset=utf-8');
    readfile($cacheFile);
    exit;
}

$ch = curl_init($framerUrl . '/sitemap.xml');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$xml  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($xml === false || $code >= 400) {
    header('HTTP/1.1 502 Bad Gateway');
    die("Could not fetch sitemap: $err (HTTP $code)");
}

$xml = str_replace($framerUrl, $myDomain, $xml);

if (!$devMode) {
    @mkdir(__DIR__ . '/cache', 0755, true);
    file_put_contents($cacheFile, $xml);
}

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
