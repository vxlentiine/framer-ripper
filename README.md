Serve a Framer site under your own domain without a paid plan. Pages are proxied on demand, all assets are cached locally, and SEO metadata is fully rewritten to your domain.

# Features

- Rewrites canonical, `og:url`, and robots meta tags to your domain
- **Robots meta guard** — client-side MutationObserver prevents Framer's JS from re-injecting `noindex` after page load
- Serves a custom `robots.txt` pointing crawlers to your sitemap
- **Self-crawling sitemap** — generates a proper `sitemap.xml` by crawling your site, including `<lastmod>` and `<priority>` (no longer relies on Framer's empty sitemap)
- **Full asset proxy** — all `framerusercontent.com` and `app.framerstatic.com` assets are routed through `/assets/` and `/static/`, cached permanently on your server
- **JS/CSS URL rewriting** — references inside JS bundles are rewritten so dynamically rendered content never contacts Framer's CDNs
- **Framer analytics removed** — strips the `events.framer.com` tracking script
- **Editor bar stubbed** — prevents `framer.com/edit/init.mjs` from loading, which blocks `app.framerstatic.com` chunk fetches for regular visitors
- Indefinite file cache — Framer is only contacted on the first request per page or asset
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

Pages, assets, and the sitemap are cached indefinitely as flat files in `cache/`. To refresh after publishing changes in Framer:

- **HTML pages** — delete `cache/*.html` or set `$devMode = true`, reload, then set it back
- **Assets (JS, images, fonts)** — delete `cache/assets/*` to force a fresh fetch of all proxied assets
- **Sitemap** — delete `cache/sitemap.xml` to trigger a fresh crawl on next request

> After publishing in Framer, always clear `cache/*.html` and `cache/assets/*` — Framer changes content-hashed JS bundle filenames on each publish, and stale cached HTML will reference dead URLs.
