# Admin CMS

Use this reference for admin sidebar, submenus, list pages, create/edit pages, permissions, Livewire manager behavior, form UX, and CMS spacing.

## Admin Navigation

- Sidebar uses one shared navigation card; groups collapse independently inside it.
- Current group must render open from server-side HTML before Alpine hydrates.
- Group open/closed state may persist in local storage, parsed defensively.
- Hide groups when the current user has no allowed child action.
- `Sliders` is its own group, not nested inside `Landing Pages`.
- Page-local submenus come from `AdminNavigationRegistry` and should render as one compact collapse card.
- Child actions should use short task labels such as `Danh sách tour`, `Tạo mới tour`, `Chủ đề tour`.

When changing admin routes, managers, roles, or sidebar labels, update registry, middleware, seeders, permission UI, and docs together.

## List Page Pattern

Active list pages should follow:

1. Page header
2. Registry-driven submenu
3. Filter toolbar
4. Table/data grid
5. Pagination

Rules:

- List pages are separate routes, not mixed with create/edit forms.
- Tables stay scannable on desktop and use horizontal overflow on mobile.
- Empty states belong inside the table body.
- Filters should be business-facing: name, publish state, category, role, scope, destination, etc.
- Tour list must include `Điểm đến` filtering when relevant.
- Review items for `Tour`, `Chủ đề tour`, and `Điểm đến` use dedicated manager routes, not embedded editor forms.

## Create/Edit Pattern

- Create/edit screens are separate routes.
- Header includes a back-to-index action.
- Long forms use sticky/fixed action bars and bottom padding so fields are not hidden.
- Delete action appears only when the record exists.
- Editors focus on one record; do not add side list panels.
- Submit feedback should use SweetAlert2 for loading, success, warning, and error states.
- Validation summary should appear near the top when possible, with field-level errors under fields.

## Travel Editors

Tour editor blocks:

- identity and publish state
- scope and taxonomy
- pricing and commercial fields
- cover/avatar
- gallery
- overview/content
- itinerary
- pricing table and inclusions
- departure repeater
- FAQ
- SEO fields

`tour_departures` is the source of truth for per-date commercial data. Prioritize editor scan order as `departure_date`, `sale_price`, `base_price`, and `standard_label`, then return date, departure place, transport, status, slots, notes, and metadata.

Taxonomy editors (`TourCategory`, `Destination`, `Region`, `Country`) need identity, publish state, avatar/cover, gallery, intro/body copy, and SEO fields.

Landing page editor is block-first:

- identity, slug, publish state
- template preset
- editor mode: `blocks` or manual `html`
- clone utility
- block picker and block stack
- manual HTML textarea only in HTML mode
- SEO/schema/meta fields
- save actions

## Rich Text And Media In Admin

- Use the shared editor wrapper for rich text fields.
- Meta, excerpt, and summary fields should prefer clean text, not HTML noise.
- Long `body` or content fields may use full rich text rendering.
- Image upload fields should reuse the shared Media popup and `x-admin.image-dropzone`.
- Adding upload fields or Quill editors requires regression checks for Media popup selection, insertion, replacement, preview, and save.
- After `wire:navigate`, shared admin JS for Media popup, image pickers, and Quill must re-bind correctly.

## Spacing And Surface

- Admin density is compact: prefer `space-y-4`, `gap-3`, `gap-4`, `p-4`, `p-5`.
- Avoid large `p-6` surfaces unless justified.
- Keep list/table row actions compact.
- Avoid turning admin pages into many unrelated cards; use cards for meaningful grouped surfaces.

