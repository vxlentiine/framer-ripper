<?php
require __DIR__ . '/config.php';

// Map the incoming request path to the remote Framer URL
$requestUri = $_SERVER['REQUEST_URI'];
$scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$path = $requestUri;
if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
if ($path === '') $path = '/';

$targetUrl = $framerUrl . $path;

// Serve from cache when not in dev mode
$cachePath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$cacheFile = __DIR__ . '/cache/' . md5($cachePath) . '.html';

if (!$devMode && file_exists($cacheFile)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($cacheFile);
    exit;
}

// Fetch from Framer
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$headers = [];
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
}
if (!empty($_SERVER['HTTP_ACCEPT'])) {
    $headers[] = 'Accept: ' . $_SERVER['HTTP_ACCEPT'];
}
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $headers[] = 'Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$html = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($html === false || $code >= 400) {
    header('HTTP/1.1 502 Bad Gateway');
    die("Could not fetch source URL: $err (HTTP $code)");
}

$origin = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

// Inject <base> pointing to our domain so relative navigation resolves here
// (Framer assets are absolute framerusercontent.com URLs and are unaffected)
if (stripos($html, '<base') === false) {
    $baseTag = '<base href="' . $myDomain . '/">';
    $html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $baseTag, $html, 1);
}

// Override Framer's internal site URL so the router uses our domain
$inject = '<script>window.__FRAMER_SITE_URL__="' . $origin . '";</script>';
$html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $inject, $html, 1);

// Fix robots meta: Framer sets noindex which would block all crawlers
$html = preg_replace(
    '/<meta\s+name=["\']robots["\'][^>]*>/i',
    '<meta name="robots" content="index, follow">',
    $html
);

// Fix canonical URL
$html = preg_replace(
    '#<link\s+rel="canonical"[^>]*>#i',
    '<link rel="canonical" href="' . $origin . $_SERVER['REQUEST_URI'] . '">',
    $html
);

// Fix og:url
$html = preg_replace(
    '#<meta\s+property="og:url"[^>]*>#i',
    '<meta property="og:url" content="' . $origin . $_SERVER['REQUEST_URI'] . '">',
    $html
);

// Strip remaining Framer domain references (makes hrefs relative; base tag resolves them to our domain)
$html = str_replace($framerUrl, '', $html);

// Inject GA4 snippet before </head> when configured
if (!empty($gaId)) {
    $gaSnippet = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $gaId . '"></script>'
        . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',\'' . $gaId . '\');</script>';
    $html = str_replace('</head>', $gaSnippet . '</head>', $html);
}

// Remove Framer badge and HTML comments
$html = preg_replace('#<div\s+id="__framer-badge-container"[^>]*>.*?</div>#si', '', $html);
$html = preg_replace('/<!--(.|\s)*?-->/', '', $html);

if (!$devMode) {
    @mkdir(__DIR__ . '/cache', 0755, true);
    file_put_contents($cacheFile, $html);
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
