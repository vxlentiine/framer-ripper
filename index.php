<?php
// Base URL to fetch (no trailing slash here is fine)
$baseUrl = 'https://strauss.framer.website';

// --- determine the requested path on our server and map to remote path ---
// REQUEST_URI contains path + query string, SCRIPT_NAME contains our script path
$requestUri = $_SERVER['REQUEST_URI'];            // e.g. "/store?x=1"
$scriptName = $_SERVER['SCRIPT_NAME'];            // e.g. "/index.php" or "/proxy.php"
$scriptDir  = rtrim(dirname($scriptName), '/');  // e.g. "" or "/subdir"

// remove the script directory prefix if present so "/subdir/store" -> "/store"
$path = $requestUri;
if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
if ($path === '') $path = '/'; // ensure at least "/"

// Build full remote URL (requestUri already includes query string)
$targetUrl = rtrim($baseUrl, '/') . $path;

// --- fetch remote content with cURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// forward a sensible User-Agent and Accept headers to make responses match a browser
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
    header("HTTP/1.1 502 Bad Gateway");
    die("Could not fetch source URL: $err (HTTP $code)");
}

// --- inject <base> so relative asset URLs resolve to the real Framer host ---
// only add if no base tag exists already
if (stripos($html, '<base') === false) {
    $baseTag = '<base href="' . rtrim($baseUrl, '/') . '/">';
    // insert base tag right after <head ...>
    $html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $baseTag, $html, 1);
}

$origin = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$inject = <<<HTML
<script>
  // Override Framer site origin so router uses our domain
  window.__FRAMER_SITE_URL__ = "{$origin}";
</script>
HTML;

$html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $inject, $html, 1);


$html = preg_replace(
    '#<link\s+rel="canonical"[^>]*>#i',
    '<link rel="canonical" href="' . $origin . $_SERVER['REQUEST_URI'] . '">',
    $html
);

$html = preg_replace(
    '#<meta\s+property="og:url"[^>]*>#i',
    '<meta property="og:url" content="' . $origin . $_SERVER['REQUEST_URI'] . '">',
    $html
);

// rewrite absolute Framer links to relative
$html = str_replace(
    ['https://strauss.framer.website'],
    [''],
    $html
);

// --- remove the Framer badge and any HTML comments (optional) ---
$html = preg_replace('#<div\s+id="__framer-badge-container"[^>]*>.*?</div>#si', '', $html);
$html = preg_replace('/<!--(.|\s)*?-->/', '', $html);

// --- output (set content-type as returned by remote if you want; here we assume text/html) ---
header('Content-Type: text/html; charset=utf-8');
echo $html;
