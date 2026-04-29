<?php
require __DIR__ . '/config.php';

$cacheFile = __DIR__ . '/cache/sitemap.xml';

if (!$devMode && file_exists($cacheFile)) {
    header('Content-Type: application/xml; charset=utf-8');
    readfile($cacheFile);
    exit;
}

function fetchPage(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SitemapCrawler/1.0)');
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response   = curl_exec($ch);
    $code       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($response === false || $code >= 400) return null;

    $headers = substr($response, 0, $headerSize);
    $html    = substr($response, $headerSize);

    $lastmod = null;
    if (preg_match('/Last-Modified:\s*(.+)/i', $headers, $m)) {
        $lastmod = date('Y-m-d', strtotime(trim($m[1])));
    }

    return ['html' => $html, 'lastmod' => $lastmod];
}

function extractLinks(string $html, string $framerHost, string $framerUrl): array
{
    $links = [];
    preg_match_all('/<a\s[^>]*\bhref=["\']([^"\'#][^"\']*)["\'][^>]*>/i', $html, $matches);

    foreach ($matches[1] as $href) {
        if (preg_match('/^(mailto:|tel:|javascript:)/i', $href)) continue;

        // Resolve relative URLs
        if (!preg_match('/^https?:\/\//i', $href)) {
            $href = rtrim($framerUrl, '/') . '/' . ltrim($href, '/');
        }

        $parsed = parse_url($href);
        if (empty($parsed['host']) || $parsed['host'] !== $framerHost) continue;

        // Normalise: path only, no query string, no fragment, no ./ segments, no trailing slash
        $path = $parsed['path'] ?? '/';
        $path = preg_replace('#/\.(/|$)#', '/', $path); // collapse /./
        $path = rtrim($path, '/') ?: '/';
        $links[] = $path;
    }

    return array_unique($links);
}

$framerHost = parse_url($framerUrl, PHP_URL_HOST);
$visited    = [];
$queue      = ['/'];
$pages      = [];
$limit      = 500;

while (!empty($queue) && count($pages) < $limit) {
    $path = array_shift($queue);

    if (isset($visited[$path])) continue;
    $visited[$path] = true;

    $page = fetchPage($framerUrl . $path);
    if ($page === null) continue;

    $pages[$path] = $page['lastmod'];

    foreach (extractLinks($page['html'], $framerHost, $framerUrl) as $link) {
        if (!isset($visited[$link])) {
            $queue[] = $link;
        }
    }
}

// Build sitemap XML
$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $path => $lastmod) {
    $loc      = rtrim($myDomain, '/') . $path;
    $priority = ($path === '/') ? '1.0' : '0.8';
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
    if ($lastmod) $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
    $xml .= '    <priority>' . $priority . '</priority>' . "\n";
    $xml .= '  </url>' . "\n";
}
$xml .= '</urlset>';

if (!$devMode) {
    @mkdir(__DIR__ . '/cache', 0755, true);
    file_put_contents($cacheFile, $xml);
}

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
