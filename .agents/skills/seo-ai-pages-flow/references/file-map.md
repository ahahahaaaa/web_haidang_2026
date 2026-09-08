# SEO AI File Map

## Primary routes

- `bootstrap/app.php`
  Confirms whether `routes/admin_seo.php`, `routes/api_v1/seo.php`, and `routes/ai.php` are actually loaded.
- `routes/admin_seo.php`
  Admin SEO list/editor pages.
- `routes/api_v1/seo.php`
  Cluster create, regenerate, QA, publish endpoints.
- `routes/ai.php`
  Prompt preview endpoint.

## Admin UI

- `resources/views/admin/seo/pages/index.blade.php`
  SEO Pages shell and onboarding copy.
- `resources/views/admin/seo/pages/edit.blade.php`
  SEO editor shell.
- `resources/views/livewire/admin/seo/pages-index.blade.php`
  Manual create form, cluster wizard, filters, page list.
- `resources/views/livewire/admin/seo/page-editor.blade.php`
  Page editor fields and workflow actions.
- `app/Livewire/Admin/Seo/SeoPagesIndex.php`
  Create/list/filter logic.
- `app/Livewire/Admin/Seo/SeoPageEditor.php`
  Save/regenerate/QA/approve/publish actions.

## Create flow

- `src/Domains/Seo/Actions/CreateSeoPageAction.php`
  Manual page creation.
- `src/Domains/Seo/Actions/CreateClusterAction.php`
  Cluster creation.
- `src/Domains/Seo/Actions/DispatchClusterGenerationAction.php`
  Starts cluster queue flow.
- `app/Http/Controllers/Admin/Seo/SeoClusterController.php`
  Cluster API entry point.
- `app/Http/Requests/Admin/Seo/StoreContentClusterRequest.php`
  Cluster validation.

## Generation and QA

- `src/Domains/Seo/Jobs/GenerateSeoBriefJob.php`
- `src/Domains/Seo/Jobs/GenerateSeoDraftJob.php`
- `src/Domains/Seo/Jobs/BuildSeoLinksJob.php`
- `src/Domains/Seo/Jobs/ValidateSeoPageJob.php`
- `src/Domains/Seo/Jobs/PublishSeoPageJob.php`

## Core support

- `src/Domains/Seo/Support/SeoPromptFactory.php`
- `src/Domains/Seo/Support/SeoMetaFactory.php`
- `src/Domains/Seo/Support/SeoSchemaFactory.php`
- `src/Domains/Seo/Support/SeoQaValidator.php`
- `src/Domains/Seo/Support/SeoSlugGenerator.php`

## Models and enums

- `src/Domains/Seo/Models/ContentCluster.php`
- `src/Domains/Seo/Models/SeoPage.php`
- `src/Domains/Seo/Models/SeoLink.php`
- `src/Domains/Seo/Enums/SeoPageType.php`
- `src/Domains/Seo/Enums/SeoPageStatus.php`
- `src/Domains/Seo/Enums/SeoClusterStatus.php`

## Contracts and config

- `examples/seo_brief_example.json`
- `examples/seo_draft_example.json`
- `config/seo_ai.php`
