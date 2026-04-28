Serve a Framer site under your own domain without a paid plan. Pages are proxied on demand, assets are served directly from Framer's CDN, and all SEO metadata is rewritten to your domain.

# Features

- Rewrites canonical, og:url, and robots meta tags to your domain
- Serves a custom `robots.txt` pointing crawlers to your sitemap
- Fetches and rewrites `sitemap.xml` with your domain's URLs
- Indefinite file cache — Framer is only hit on the first request per page
- Optional Google Analytics 4 injection
- Removes the Framer badge

# Requirements

- Apache with `mod_rewrite` (included `.htaccess` handles all routing)
- PHP 8+ with cURL

# Setup

1. Copy `config.example.php` to `config.php` and fill in your values:

```php
$framerUrl = 'https://yoursite.framer.website'; // your Framer project URL
$myDomain  = 'https://yourdomain.tld';          // your domain
$gaId      = 'G-XXXXXXXXXX';                    // GA4 ID, or leave empty
$devMode   = false;                              // set true to bypass cache
```

2. Upload all files to your web root.
3. Point your domain's DNS to the server.

# Cache

Pages and the sitemap are cached indefinitely as flat files in `cache/`. To refresh after publishing changes in Framer, either set `$devMode = true` and reload the pages you need refreshed, then set it back to `false` — or delete the files in `cache/` directly.
