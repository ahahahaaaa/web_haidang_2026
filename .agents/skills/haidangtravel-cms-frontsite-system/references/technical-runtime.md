# Technical Runtime

Use this reference for project-wide architecture, active runtime boundaries, cache behavior, route families, inquiry flow, and cross-cutting CMS contracts.

## Active Runtime

- Theme: `haidangtravel`.
- Active domains: `Tour`, `TourCategory`, `Destination`, `Region`, `Country`, `Service`, `BlogPost`, `LandingPage`, `Slider`, `TravelInquiry`, `Menu`, `SiteSetting`.
- Public IA: home, about, contact, service listing/detail, blog listing/detail, tour scope pages, tour taxonomy pages, tour detail pages, and custom landing pages.
- Service category hub path: `/dich-vu/danh-muc/{slug}`.
- Public theme views should live under `resources/views/themes/haidangtravel`.

Do not reactivate construction, estimator, package, project, old SEO AI, or legacy theme runtime.

## Stack Defaults

- Backend: Laravel, Livewire CMS managers, Spatie roles/permissions, Spatie Media Library.
- Frontsite: server-rendered Blade, Tailwind styling, shared inquiry modal, optional slider/gallery behavior only where useful.
- Admin UI: Livewire plus Flux UI where already used.
- Controllers coordinate; validation and authorization stay explicit; reusable logic belongs in services/actions/jobs/models.

## Public Routes

Primary travel routes:

- `/`
- `/ve-chung-toi`
- `/tour-trong-nuoc`
- `/tour-nuoc-ngoai`
- `/tour-doan`
- `/danh-muc-tour/{slug}`
- `/tour-{slug}` for destination hubs
- `/vung-mien/{slug}`
- `/tour-{slug}` for country root hubs
- `/chuong-trinh/{slug}` for tour detail
- `/dich-vu`
- `/dich-vu/danh-muc/{slug}`
- `/dich-vu/{slug}`
- `/blog`
- `/danh-muc/{slug}` for blog category hubs
- `/{slug-category}/{slug-blog}`
- `/lien-he`
- `/{custom-landing-slug}` last

Custom landing root slugs must reject reserved public/admin paths before save or publish.

## Frontsite Cache

- Cache scope is public frontsite only.
- Admin, API, Livewire manager screens, non-GET requests, and write requests must bypass response cache.
- Public response caching is attached through `frontsite.cache` middleware in `routes/frontsite.php`.
- Runtime ownership lives in `App\Services\Frontsite\FrontsiteCache`, `FrontsiteCacheInvalidator`, `CacheFrontsiteResponse`, and the `frontsite:cache:*` commands.
- Clear by versioned frontsite groups, never by broad `Cache::flush()`.
- CSRF must remain safe under cached HTML by using the response-cache placeholder/restore flow.
- Session feedback, old input, validation errors, and inquiry success/error flashes must bypass stale cached markup.
- When public models, media collections, menus, sliders, landing blocks, or shared view composers can change rendered HTML, update `FrontsiteCacheInvalidator`.

Useful commands:

```powershell
php artisan frontsite:cache:clear
php artisan frontsite:cache:clear home tours blog services landing chrome menus settings sliders taxonomies sitemap
php artisan frontsite:cache:warm
php artisan frontsite:cache:warm --limit=1
```

## Travel Inquiry

- Public post route remains `/yeu-cau-tu-van`.
- All public inquiry submissions write to `TravelInquiry`.
- Context for `tour`, `service`, and `general` stays normalized through `source`, `context_title`, and optional `meta`.
- Extended fields such as inquiry type, company name, address, subject, and group size should go in `meta` unless a wider schema change is explicitly approved.
- Shared modal is the default CTA surface; approved inline variants must still post into the same contract.
- JavaScript-enabled forms should submit via Ajax and render field errors/success inside the same modal or panel.
- Redirect plus session flash is a no-JS fallback.

## Navigation And Permissions

Source of truth: `App\Support\Admin\AdminNavigationRegistry`.

Keep aligned:

- desktop sidebar
- mobile sidebar
- page-local admin submenus
- seeded permissions and roles
- account permission matrix
- route middleware
- docs

Current CMS groups:

- `Quản lý tour`
- `Quản lý dịch vụ`
- `Quản lý blog`
- `Landing Pages`
- `Sliders`
- `Travel Inquiries`
- `Media`
- `Menus & Theme`
- `Tài khoản`

Permissions follow child-action keys: list routes use `.index`; create/edit/delete write flows use `.edit`. `Admin` has full access. `Content` defaults to blog list/edit plus blog category list/edit. `super_admin` remains compatibility full access.
