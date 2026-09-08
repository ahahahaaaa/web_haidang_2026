---
name: media-spatie-library
description: >-
  Use when working on the shared Spatie media library flow for Quill insert
  image, media popup selection, admin image upload fields, library metadata,
  and model-to-library image syncing across the Haidang Travel CMS.
---

# Media Spatie Library

Use this skill when the task touches the shared media popup, Quill image insertion, admin upload fields, or Spatie Media Library sync logic.

## Always Read First

- `docs/AGENTS.md`
- `docs/ADMIN_CMS.md`
- `docs/TECHNICAL_REQUIREMENTS.md`

## Read When Needed

- `references/runtime-contract.md`
- `references/callsites.md`

## Use This Skill When

- the user wants a media popup for Quill,
- the user wants a media popup for any admin image field,
- the user wants a new upload field to reuse the shared library flow,
- the user wants to edit library metadata or upload-to-library behavior,
- the user wants to sync an uploaded or library-selected image into a Spatie media collection.

## Core Rules

- Reuse the existing shared media popup. Do not create one-off upload modals when the existing browser can handle the flow.
- Quill image insert and field picker mode share the same popup runtime in `resources/js/admin/quill.js`.
- Shared library uploads go into collection `library` through `MediaLibraryUploader`.
- Admin image fields should prefer `x-admin.image-dropzone` plus `data-admin-media-picker-trigger`.
- Livewire model-bound pickers should use `HandlesMediaUploads` instead of custom copy logic.
- Alt text should come from explicit input first, otherwise from the selected library media.
- If the task changes browse/upload APIs, update both the JS modal flow and the matching controllers/services.

## Workflow

### 1. Identify the mode

Choose one mode first:

- Quill insert image
- picker for a model-bound upload field
- library upload/metadata management

### 2. Reuse the shared picker contract

For picker buttons:

- use `data-admin-media-picker-trigger`
- pass the Livewire component id
- pass the target upload property
- pass the alt target when the form should inherit library alt text

### 3. Use the shared Livewire sync path

For model-bound images:

- use `selectLibraryMediaForUpload()` or the component wrapper method
- persist with `syncSingleImageSelection()` or the matching helper
- avoid hand-written per-component copy logic unless the model needs special media rules

### 4. Keep library behavior centralized

When changing the popup or the library manager:

- update the modal view
- update `resources/js/admin/quill.js`
- update browser/upload services or controllers if the payload changes

### 5. Verify representative surfaces

Check at least:

- one Quill field with image insert
- one `x-admin.image-dropzone` field with media popup
- one library upload or metadata edit path when the task touched shared library behavior

## Deliverables

The task is complete when:

- the change reuses the shared popup and library flow,
- Quill and upload-field picker behavior stay aligned,
- Spatie collection sync remains explicit,
- alt/name metadata behavior is preserved,
- no redundant upload runtime was introduced.
