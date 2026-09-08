# Call Sites

Use this file when you need representative CMS surfaces that already use the shared media popup pattern.

## Quill + popup examples

- `resources/views/livewire/admin/cms/blogs/editor.blade.php`
- `resources/views/livewire/admin/cms/landing-pages-manager.blade.php`

## Dropzone + popup examples

- `resources/views/livewire/admin/cms/sliders-manager.blade.php`
- `resources/views/livewire/admin/cms/partials/service-basics.blade.php`
- `resources/views/livewire/admin/cms/partials/service-gallery.blade.php`
- `resources/views/livewire/admin/cms/partials/tour-gallery.blade.php`
- `resources/views/livewire/admin/cms/theme-settings-manager.blade.php`

## Quick grep

Use these search patterns before adding a new upload surface:

- `rg -n "data-admin-media-picker-trigger" resources/views/livewire/admin/cms -g "*.blade.php"`
- `rg -n "x-admin.image-dropzone" resources/views/livewire/admin/cms resources/views/components -g "*.blade.php"`

## Guidance

- Start from the closest existing surface instead of inventing a new picker contract.
- If the new field only needs one image, copy a single-image selection pattern.
- If the field is rich text, reuse the Quill insert-image flow instead of wiring a second editor-specific modal.
