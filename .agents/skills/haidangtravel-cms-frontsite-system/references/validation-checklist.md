# Validation Checklist

Use the smallest relevant validation set for the changed scope. Report what ran and what was not run.

## General

```powershell
composer dump-autoload -o
php artisan route:list
php artisan test
npm run build
```

Run focused tests when available and sufficient. Do not skip route and authorization checks when touching CMS navigation, roles, or middleware.

## Admin CMS

Check:

- sidebar groups/actions match `AdminNavigationRegistry`
- current group opens from server-rendered HTML
- mobile and desktop sidebar filtering follows permissions
- page-local submenu renders for current group
- index/create/edit/delete routes resolve
- list filters preserve pagination state
- save/delete/back-to-index flow works
- sticky/fixed action bar does not hide fields
- SweetAlert2 feedback appears for save/error/destructive actions

## Permissions

Check:

- route middleware uses the same permission key as registry
- list routes use `.index`
- create/edit/delete use `.edit`
- `Admin` keeps full access
- `Content` defaults to blog list/edit and blog category list/edit
- account permission matrix mirrors sidebar groups
- no permissions are created for deprecated runtime screens

## Media, Quill, And Uploads

Representative checks:

- one Quill field inserts an image through Media popup
- one `x-admin.image-dropzone` field opens the shared picker
- selecting library media updates preview and alt text where supported
- replacement and save persist to the expected Spatie collection
- upload/library metadata path still works if touched
- popup and Quill bindings still work after `wire:navigate`

When conversion definitions change:

```powershell
php artisan media-library:regenerate --only=small --only=medium --only=full --force --no-interaction
php artisan media-library:clean --force --no-interaction
```

## Frontsite UX

Check:

- theme `haidangtravel` renders
- public route does not call legacy construction theme/views
- one visible `H1`
- header/footer links are travel IA only
- primary CTA opens shared inquiry modal
- mobile and desktop are usable
- visible focus states and keyboard access work
- reduced motion does not hide content
- public selects use Tom Select and date fields use Flatpickr where applicable

## Inquiry

Check:

- all public inquiry forms write to `TravelInquiry`
- Ajax submit returns JSON success/error and renders inside the modal/panel
- field errors appear under fields
- no-JS fallback redirects with feedback
- cached pages still submit with valid CSRF
- mail failure does not discard captured inquiry metadata

## SEO And Schema

Check:

- canonical URL resolves to intended base page
- filtered listing variants canonicalize or noindex appropriately
- sitemap includes only canonical active travel pages
- visible FAQ exists before emitting `FAQPage`
- schema uses visible page content and real CMS fields
- tour offers use the same published departure rows as the UI
- no fake reviews, ratings, prices, awards, certifications, or departure dates
- blog TOC is generated only from rendered `h2` headings
- GA4 and Facebook Pixel render on public pages when configured

## Frontsite Cache

For cache-sensitive changes:

```powershell
php artisan frontsite:cache:clear
php artisan frontsite:cache:warm --limit=1
```

Then verify one public page cycle:

- first response has `X-Frontsite-Cache: MISS`
- next response has `X-Frontsite-Cache: HIT`
- inquiry form CSRF/session feedback still behaves correctly
- related `FrontsiteCacheInvalidator` groups cover changed model/view data

