<?php
require __DIR__ . '/config.php';

$prefix     = strpos($_SERVER['REQUEST_URI'], '/fstatic') === 0 ? '/fstatic' : '/assets';
$targetHost = ($prefix === '/fstatic') ? 'app.framerstatic.com' : 'framerusercontent.com';
$assetPath  = preg_replace('#^' . $prefix . '#', '', $_SERVER['REQUEST_URI']);

$cacheDir  = __DIR__ . '/cache/assets';
$cacheKey  = md5($assetPath);
$cacheFile = $cacheDir . '/' . $cacheKey;
$metaFile  = $cacheFile . '.meta';

if (!$devMode && file_exists($cacheFile) && file_exists($metaFile)) {
    $meta = json_decode(file_get_contents($metaFile), true);
    header('Content-Type: ' . $meta['type']);
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($cacheFile);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . $targetHost . $assetPath);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
}
$body = curl_exec($ch);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $code >= 400) {
    header('HTTP/1.1 502 Bad Gateway');
    exit;
}

// Rewrite Framer CDN references inside text-based assets (JS, CSS, JSON)
// so that dynamically rendered content also routes through our asset proxy
if (preg_match('#(javascript|css|json|text/)#i', $type)) {
    $body = str_replace('https://framerusercontent.com', '/assets', $body);
    $body = str_replace('https://app.framerstatic.com', '/fstatic', $body);
}

if (!$devMode) {
    @mkdir($cacheDir, 0755, true);
    file_put_contents($cacheFile, $body);
    file_put_contents($metaFile, json_encode(['type' => $type]));
}

header('Content-Type: ' . $type);
header('Cache-Control: public, max-age=31536000, immutable');
echo $body;
