User-agent: *
Disallow: /admin/
Disallow: /login
Disallow: /cart
Disallow: /checkout
Disallow: /search
Disallow: /*?utm_
Disallow: /*?sort=
Disallow: /*?filter=
Disallow: /*?departure=

Sitemap: {{ \App\Support\FrontsiteUrls::canonicalUrl($sitemapUrl) }}