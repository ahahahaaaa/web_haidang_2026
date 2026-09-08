# Media, Quill, And Spatie

Use this reference when working on shared media popup, Quill image insertion, admin upload fields, media library metadata, Spatie collections, conversions, and image troubleshooting.

For implementation details, also load `.agents/skills/media-spatie-library/SKILL.md`.

## Core Contract

- Reuse the existing shared Media popup. Do not create one-off upload modals or separate upload runtimes for travel CMS fields when the shared popup can serve the need.
- Quill image insert and model-bound image picker mode share the same popup runtime in `resources/js/admin/quill.js`.
- Shared library uploads go to the `library` collection through `MediaLibraryUploader`.
- Admin image fields should prefer `x-admin.image-dropzone` with `data-admin-media-picker-trigger`.
- Livewire model-bound pickers should use `HandlesMediaUploads`.
- Alt text comes from explicit input first, then from selected library media metadata.
- If popup/upload payloads change, update JS modal flow, views, controllers, and services together.

## Preferred Collections

Single representative visuals:

- `Tour`, `Service`, `BlogPost`: `cover`
- `TourCategory`, `Destination`, `Region`, `ContentCategory`: `avatar`
- `SliderItem`: `image`, `mobile_image`, `inner_image`
- `SiteSetting`: `logo`, `og_image`
- `LandingPage`: hero/gallery collections resolved through `LandingPageBlocks` and `LandingPageVisuals`

Direct URL fallback fields such as `cover_image_url`, `logo_url`, or `og_image_url` may remain for compatibility, but preferred runtime is Spatie-managed media with conversions.

## Conversion Contract

Shared frontsite conversions:

- `small`: `500x500`
- `medium`: `1000x1000`
- `full`: `2400x2400`

Rules:

- Use fit-max/editorial-safe resizing, not hard crop assumptions.
- Preserve original image format when possible so transparent PNG logos/avatars keep alpha.
- Shared frontsite conversions run non-queued so core runtime image sizes do not depend on a queue worker.

Usage map:

- `small`: OG/social preview, standard cards, taxonomy cards, blog category cards, process cards, topic rails, static gallery tiles, thumbnails.
- `medium`: primary in-page stage/main image of non-hero sliders or galleries.
- `full`: hero surfaces, hero side media, full-size open/lightbox/zoom targets.
- Tour detail gallery uses `medium` for in-page stage and `full` for lightbox.
- Destination and TourCategory detail galleries are exceptions: visible stage uses `full`, thumbnails use `small`.

## Quill And Rich Text

- Use shared Quill wrapper and Media popup for insert image.
- Do not wire editor-specific upload modals.
- Verify image insertion after admin view changes with `wire:navigate`.
- Keep rich body fields as rich text; keep meta/excerpt/summary as clean text where possible.

## Regeneration And Troubleshooting

When conversion definitions change, regenerate existing files without `--only-missing`:

```powershell
php artisan media-library:regenerate --only=small --only=medium --only=full --force --no-interaction
php artisan media-library:clean --force --no-interaction
```

Use `--only-missing` only when definitions are unchanged and missing derived files need backfill.

If images do not display:

```powershell
php artisan optimize:clear
php artisan storage:link
php artisan media-library:regenerate --only=small --only=medium --only=full --force --no-interaction
php artisan media-library:clean --force --no-interaction
```

If `/storage/...` returns `403` while page HTML returns `200`, verify the referenced file exists. Missing files may fall through to Laravel's `storage/{path}` route. Regeneration only works if the original media file exists.

