<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Support\FaqContent;
use App\Support\LandingPageBlocks;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class ContentWriteContractService
{
    public const VERSION = 'cms-content-contract-v3';

    public const BLOCK_CHANGES = 'block_changes';

    public const BLOCK_STRING_FIELDS = [
        'answer_summary', 'badge_label', 'card_cta_label', 'countdown_expired_label',
        'countdown_label', 'cta_label', 'description', 'excerpt', 'eyebrow', 'html',
        'kicker', 'media_alt', 'modal_description', 'modal_title', 'offer_code',
        'offer_label', 'offer_note', 'panel_description', 'panel_eyebrow', 'panel_title',
        'primary_label', 'secondary_label', 'tag_label', 'title', 'title_highlight',
        'title_prefix', 'title_suffix', 'trust_note',
    ];

    /** @return array<string, array<string, mixed>> */
    public function fieldContracts(SeoOptimizationPage $page, ?Model $source = null): array
    {
        $source ??= $this->source($page);
        if (! $source) {
            return [];
        }

        $contracts = $this->fieldDefinitions($page, $source);
        if (isset($contracts[self::BLOCK_CHANGES])) {
            $contracts[self::BLOCK_CHANGES]['block_types'] = collect($this->landingBlockDefinitions())
                ->mapWithKeys(fn (array $fields, string $type): array => [$type => [
                    'label' => LandingPageBlocks::blockTypes()[$type] ?? $type,
                    'fields' => $fields,
                ]])
                ->all();
        }

        return $contracts;
    }

    /** @return array<int, string> */
    public function writableFields(SeoOptimizationPage $page, ?Model $source = null): array
    {
        return array_keys($this->fieldContracts($page, $source));
    }

    /** @return array<string, mixed> */
    public function sourceFields(SeoOptimizationPage $page, ?Model $source = null): array
    {
        $source ??= $this->source($page);
        if (! $source) {
            return [];
        }

        $fields = collect($this->writableFields($page, $source))
            ->reject(fn (string $field): bool => $field === self::BLOCK_CHANGES)
            ->filter(fn (string $field): bool => array_key_exists($field, $source->getAttributes()))
            ->mapWithKeys(fn (string $field): array => [$field => $source->getAttribute($field)])
            ->all();

        if ($source instanceof LandingPage) {
            $fields['editor_mode'] = $source->isHtmlMode()
                ? LandingPage::EDITOR_MODE_HTML
                : LandingPage::EDITOR_MODE_BLOCKS;
        }

        return $fields;
    }

    /** @return array<int, array<string, mixed>> */
    public function contentUnits(SeoOptimizationPage $page, ?Model $source = null): array
    {
        $source ??= $this->source($page);
        if (! $source instanceof LandingPage || $source->isHtmlMode()) {
            return [];
        }

        $definitions = $this->landingBlockDefinitions();
        $rawBlocks = is_array($source->blocks) ? array_values($source->blocks) : [];
        $uuidCounts = collect($rawBlocks)
            ->map(fn (mixed $block): string => is_array($block) ? (string) ($block['uuid'] ?? '') : '')
            ->filter()
            ->countBy();

        return collect(LandingPageBlocks::normalize($rawBlocks))
            ->filter(fn (array $block, int $index): bool => filled($rawBlocks[$index]['uuid'] ?? null)
                && ($uuidCounts[(string) $block['uuid']] ?? 0) === 1
                && (bool) ($block['is_enabled'] ?? true))
            ->map(function (array $block, int $index) use ($definitions): ?array {
                $type = (string) ($block['type'] ?? '');
                $fields = $definitions[$type] ?? [];
                if ($fields === []) {
                    return null;
                }

                return [
                    'uuid' => (string) ($block['uuid'] ?? ''),
                    'type' => $type,
                    'label' => sprintf('%s #%d', LandingPageBlocks::blockTypes()[$type] ?? $type, $index + 1),
                    'position' => $index,
                    'fields' => collect($fields)
                        ->filter(fn (array $definition, string $field): bool => array_key_exists($field, $block))
                        ->mapWithKeys(fn (array $definition, string $field): array => [$field => [
                            ...$definition,
                            'value' => $block[$field],
                        ]])
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function valuesFromSnapshot(array $patch, array $snapshot): array
    {
        $values = [];
        foreach ($patch as $field => $value) {
            if ($field !== self::BLOCK_CHANGES) {
                $values[$field] = data_get($snapshot, 'source_fields.'.$field);

                continue;
            }

            $units = collect($snapshot['content_units'] ?? [])->keyBy('uuid');
            $values[$field] = collect(is_array($value) ? $value : [])->map(function (array $change) use ($units): array {
                $unit = $units->get((string) ($change['uuid'] ?? ''), []);

                return [
                    'uuid' => (string) ($change['uuid'] ?? ''),
                    'type' => (string) ($change['type'] ?? ''),
                    'changes' => collect($change['changes'] ?? [])->mapWithKeys(
                        fn (mixed $_value, string $key): array => [$key => data_get($unit, 'fields.'.$key.'.value')],
                    )->all(),
                ];
            })->values()->all();
        }

        return $values;
    }

    public function sourceMatches(SeoOptimizationPage $page, Model $source, array $expected): bool
    {
        $snapshot = [
            'source_fields' => $this->sourceFields($page, $source),
            'field_contracts' => $this->fieldContracts($page, $source),
            'content_units' => $this->contentUnits($page, $source),
        ];

        return $this->snapshotMatchesPatch($page, $expected, $snapshot);
    }

    public function snapshotMatchesPatch(SeoOptimizationPage $page, array $expected, array $snapshot): bool
    {
        $current = $this->valuesFromSnapshot($expected, $snapshot);

        foreach ($this->comparisonRows($page, $expected, $current, $snapshot) as $row) {
            if (! $this->valuesEquivalent($row['kind'], $row['after'], $row['before'])) {
                return false;
            }
        }

        return true;
    }

    public function apply(SeoOptimizationPage $page, Model $source, array $patch): void
    {
        $blockChanges = $patch[self::BLOCK_CHANGES] ?? null;
        unset($patch[self::BLOCK_CHANGES]);

        foreach ($patch as $field => $value) {
            $source->setAttribute($field, $value);
        }

        if ($blockChanges !== null) {
            if (! $source instanceof LandingPage || $source->isHtmlMode()) {
                throw ValidationException::withMessages(['patch' => 'Chỉ LandingPage ở chế độ blocks mới nhận block_changes.']);
            }

            $blocks = LandingPageBlocks::normalize($source->blocks ?? []);
            foreach ($blockChanges as $change) {
                $index = collect($blocks)->search(fn (array $block): bool => (string) ($block['uuid'] ?? '') === (string) ($change['uuid'] ?? '')
                    && (string) ($block['type'] ?? '') === (string) ($change['type'] ?? '')
                );
                if ($index === false) {
                    throw ValidationException::withMessages(['patch' => 'Block LandingPage đã bị xóa hoặc đổi loại sau khi duyệt.']);
                }
                foreach ($change['changes'] ?? [] as $field => $value) {
                    $blocks[$index][$field] = $value;
                }
            }

            $this->syncLandingCompatibility($source, $blocks);
        }

        $source->save();
    }

    public function assertSlugAvailable(SeoOptimizationPage $page, Model $source, string $slug): void
    {
        if ($source instanceof LandingPage && in_array($slug, LandingPageBlocks::reservedSlugs(), true)) {
            throw ValidationException::withMessages(['slug' => 'Slug LandingPage đang được route hệ thống sử dụng.']);
        }
        if ($source instanceof ContentCategory && $source->taxonomy === 'blog' && in_array($slug, [
            ...LandingPageBlocks::reservedSlugs(),
            'api', 'ai', 'build', 'diem-thuong', 'khuyen-mai', 'livewire', 'storage', 'tim-tour',
        ], true)) {
            throw ValidationException::withMessages(['slug' => 'Slug danh mục bài viết xung đột với route public của hệ thống.']);
        }

        $query = $source->newQuery()
            ->where('slug', $slug)
            ->where($source->getKeyName(), '!=', $source->getKey());

        if ($source instanceof ContentCategory) {
            $query->where('taxonomy', $source->taxonomy);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'Slug đã được dùng bởi nội dung cùng loại.']);
        }
    }

    /** @return array<string, mixed> */
    public function restorePatch(array $appliedPatch, array $contentSnapshot): array
    {
        $restore = [];
        foreach ($appliedPatch as $field => $value) {
            if ($field !== self::BLOCK_CHANGES) {
                $restore[$field] = data_get($contentSnapshot, $field);

                continue;
            }

            $blocks = collect(LandingPageBlocks::normalize($contentSnapshot['blocks'] ?? []))->keyBy('uuid');
            $restore[$field] = collect($value)->map(function (array $change) use ($blocks): array {
                $block = $blocks->get((string) ($change['uuid'] ?? ''), []);

                return [
                    'uuid' => (string) ($change['uuid'] ?? ''),
                    'type' => (string) ($change['type'] ?? ''),
                    'changes' => collect($change['changes'] ?? [])->mapWithKeys(
                        fn (mixed $_value, string $key): array => [$key => data_get($block, $key)],
                    )->all(),
                ];
            })->values()->all();
        }

        return $restore;
    }

    /** @return array<int, array<string, mixed>> */
    public function comparisonRows(SeoOptimizationPage $page, array $patch, array $before, array $snapshot = []): array
    {
        $contracts = $snapshot['field_contracts'] ?? $this->fieldContracts($page);
        $units = collect($snapshot['content_units'] ?? [])->keyBy('uuid');
        $rows = [];

        foreach ($patch as $field => $after) {
            if ($field !== self::BLOCK_CHANGES) {
                $rows[] = [
                    'key' => $field,
                    'label' => data_get($contracts, $field.'.label', $field),
                    'kind' => data_get($contracts, $field.'.kind', 'plain_text'),
                    'group' => null,
                    'before' => $before[$field] ?? null,
                    'after' => $after,
                ];

                continue;
            }

            $beforeBlocks = collect($before[$field] ?? [])->keyBy('uuid');
            foreach ($after as $change) {
                $uuid = (string) ($change['uuid'] ?? '');
                $type = (string) ($change['type'] ?? '');
                $unit = $units->get($uuid, []);
                $beforeChanges = data_get($beforeBlocks->get($uuid, []), 'changes', []);
                foreach ($change['changes'] ?? [] as $blockField => $value) {
                    $definition = data_get($unit, 'fields.'.$blockField)
                        ?: data_get($contracts, self::BLOCK_CHANGES.'.block_types.'.$type.'.fields.'.$blockField, []);
                    $rows[] = [
                        'key' => self::BLOCK_CHANGES.'.'.$uuid.'.'.$blockField,
                        'label' => $definition['label'] ?? $blockField,
                        'kind' => $definition['kind'] ?? 'plain_text',
                        'group' => $unit['label'] ?? ((LandingPageBlocks::blockTypes()[$type] ?? $type).' · '.$uuid),
                        'before' => $beforeChanges[$blockField] ?? null,
                        'after' => $value,
                    ];
                }
            }
        }

        return $rows;
    }

    public function textFromPatch(array $patch): string
    {
        $strings = [];
        $content = [];
        foreach ($patch as $field => $value) {
            if ($field === self::BLOCK_CHANGES) {
                foreach (is_array($value) ? $value : [] as $change) {
                    $content[] = is_array($change) ? ($change['changes'] ?? []) : [];
                }
            } else {
                $content[] = $value;
            }
        }
        array_walk_recursive($content, function (mixed $value) use (&$strings): void {
            if (is_string($value)) {
                $strings[] = RichText::normalizePlain($value);
            }
        });

        return trim(implode(' ', array_filter($strings)));
    }

    public function textFromContentUnits(array $units): string
    {
        $content = collect($units)->flatMap(
            fn (array $unit): array => collect($unit['fields'] ?? [])->pluck('value')->all(),
        )->all();

        return $this->textFromPatch($content);
    }

    public function valuesEquivalent(?string $kind, mixed $expected, mixed $actual): bool
    {
        if ($kind === 'faq') {
            return FaqContent::normalizeItems(is_array($expected) ? $expected : [])
                === FaqContent::normalizeItems(is_array($actual) ? $actual : []);
        }

        return $expected === $actual;
    }

    /** @return array<string, array<string, mixed>> */
    private function fieldDefinitions(SeoOptimizationPage $page, Model $source): array
    {
        if ($source instanceof LandingPage) {
            $fields = [
                'title' => $this->plain('Tiêu đề trang', 255),
                'meta_title' => $this->plain('SEO title', 255),
                'meta_description' => $this->plain('Meta description', 500),
            ];
            if (! $source->isSystemPage()) {
                $fields['slug'] = $this->slug('Slug');
            }
            if ($source->isHtmlMode()) {
                $fields['body'] = $this->manualHtml('HTML thủ công');
            } elseif ($this->contentUnits($page, $source) !== []) {
                $fields[self::BLOCK_CHANGES] = [
                    'label' => 'Nội dung block LandingPage',
                    'kind' => 'landing_blocks',
                    'target' => 'blocks',
                ];
            }

            return $fields;
        }

        return match ($page->page_type) {
            'tour', 'blog_post', 'service' => [
                'title' => $this->plain('Tiêu đề', 255),
                'slug' => $this->slug('Slug'),
                'excerpt' => $this->plain('Mô tả ngắn'),
                'content' => $this->richHtml('Nội dung'),
                'meta_title' => $this->plain('SEO title', 255),
                'meta_description' => $this->plain('Meta description', 500),
                'cover_alt' => $this->plain('Alt ảnh đại diện', 255),
                'faq_items' => $this->faq('FAQ'),
            ],
            'tour_category', 'destination', 'region' => [
                'name' => $this->plain('Tên hiển thị', 255),
                'slug' => $this->slug('Slug'),
                'excerpt' => $this->plain('Mô tả ngắn'),
                'content' => $this->richHtml('Nội dung'),
                'meta_title' => $this->plain('SEO title', 255),
                'meta_description' => $this->plain('Meta description', 500),
                'cover_alt' => $this->plain('Alt ảnh đại diện', 255),
                'faq_items' => $this->faq('FAQ'),
            ],
            'country' => [
                'name' => $this->plain('Tên quốc gia', 255),
                'slug' => [...$this->slug('Slug quốc gia'), 'normalizer' => 'country_slug'],
                'excerpt' => $this->plain('Mô tả ngắn'),
                'content' => $this->richHtml('Nội dung'),
                'meta_title' => $this->plain('SEO title', 255),
                'meta_description' => $this->plain('Meta description', 500),
                'cover_alt' => $this->plain('Alt ảnh đại diện', 255),
                'faq_items' => $this->faq('FAQ'),
            ],
            'blog_category' => [
                'name' => $this->plain('Tên danh mục bài viết', 255),
                'slug' => $this->slug('Slug danh mục'),
                'description' => $this->plain('Mô tả danh mục'),
                'faq_items' => $this->faq('FAQ'),
            ],
            'service_category' => [
                'name' => $this->plain('Tên danh mục dịch vụ', 255),
                'slug' => $this->slug('Slug danh mục'),
                'description' => $this->plain('Mô tả danh mục'),
                'content' => $this->richHtml('Nội dung danh mục'),
            ],
            default => [],
        };
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    private function landingBlockDefinitions(): array
    {
        $hero = [
            'eyebrow' => $this->plain('Nhãn mở đầu', 255),
            'title' => $this->plain('Tiêu đề', 255),
            'description' => $this->plain('Mô tả'),
            'primary_label' => $this->plain('Nhãn CTA chính', 255),
            'secondary_label' => $this->plain('Nhãn CTA phụ', 255),
        ];
        $listing = [
            'eyebrow' => $this->plain('Nhãn mở đầu', 255),
            'title' => $this->plain('Tiêu đề', 255),
            'description' => $this->plain('Mô tả'),
        ];

        return [
            LandingPageBlocks::TYPE_HERO_SLIDER => $hero,
            LandingPageBlocks::TYPE_HERO_MEDIA => [
                ...$hero,
                'media_alt' => $this->plain('Alt ảnh hero', 255),
            ],
            LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE => [
                'title' => $this->plain('Tiêu đề', 255),
                'description' => $this->plain('Mô tả'),
                'media_alt' => $this->plain('Alt ảnh nền', 255),
                'panel_title' => $this->plain('Tiêu đề panel', 255),
                'primary_label' => $this->plain('Nhãn CTA chính', 255),
                'secondary_label' => $this->plain('Nhãn CTA phụ', 255),
            ],
            LandingPageBlocks::TYPE_GALLERY_SLIDER => $listing,
            LandingPageBlocks::TYPE_GALLERY_MEDIA => $listing,
            LandingPageBlocks::TYPE_GEO_ANSWER => [
                'title' => $this->plain('Tiêu đề GEO', 255),
                'answer_summary' => $this->plain('Câu trả lời trực tiếp'),
            ],
            LandingPageBlocks::TYPE_HTML_WIDGET => [
                'html' => $this->manualHtml('HTML widget'),
            ],
            LandingPageBlocks::TYPE_RICH_TEXT => [
                'eyebrow' => $this->plain('Nhãn mở đầu', 255),
                'title' => $this->plain('Tiêu đề', 255),
                'excerpt' => $this->plain('Mô tả ngắn'),
                'body' => $this->richHtml('Nội dung rich text'),
            ],
            LandingPageBlocks::TYPE_REGION_RAIL => [
                'title' => $this->plain('Tiêu đề', 255),
                'description' => $this->plain('Mô tả'),
                'card_cta_label' => $this->plain('Nhãn CTA card', 255),
            ],
            LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS => [
                'title' => $this->plain('Tiêu đề', 255),
                'description' => $this->plain('Mô tả'),
                'cta_label' => $this->plain('Nhãn CTA', 255),
                'card_cta_label' => $this->plain('Nhãn CTA card', 255),
            ],
            LandingPageBlocks::TYPE_TOPIC_RAIL => $listing,
            LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS => [
                'title' => $this->plain('Tiêu đề', 255),
                'description' => $this->plain('Mô tả'),
                'cta_label' => $this->plain('Nhãn CTA', 255),
            ],
            LandingPageBlocks::TYPE_TRUST_PROOF => [
                'title' => $this->plain('Tiêu đề', 255),
                'description' => $this->plain('Mô tả'),
            ],
            LandingPageBlocks::TYPE_CTA => [
                'title' => $this->plain('Tiêu đề CTA', 255),
                'description' => $this->plain('Mô tả CTA'),
                'primary_label' => $this->plain('Nhãn CTA chính', 255),
                'secondary_label' => $this->plain('Nhãn CTA phụ', 255),
            ],
            LandingPageBlocks::TYPE_FAQ => [
                'title' => $this->plain('Tiêu đề FAQ', 255),
                'items' => $this->faq('Danh sách FAQ'),
            ],
            LandingPageBlocks::TYPE_TOUR_LIST => $listing,
            LandingPageBlocks::TYPE_BLOG_LIST => $listing,
            LandingPageBlocks::TYPE_VOUCHER_PROMOTION => $this->voucherDefinitions(),
            LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM => $this->voucherDefinitions(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function voucherDefinitions(): array
    {
        return collect([
            'badge_label', 'tag_label', 'kicker', 'title_prefix', 'title_highlight', 'title_suffix',
            'description', 'offer_label', 'offer_code', 'offer_note', 'countdown_label',
            'countdown_expired_label', 'panel_eyebrow', 'panel_title', 'panel_description',
            'primary_label', 'secondary_label', 'trust_note', 'modal_title', 'modal_description',
        ])->mapWithKeys(fn (string $field): array => [$field => $this->plain(Str::headline($field), $field === 'description' || $field === 'modal_description' ? null : 255)])->all();
    }

    private function syncLandingCompatibility(LandingPage $page, array $blocks): void
    {
        $blocks = LandingPageBlocks::normalize($blocks);
        $hero = LandingPageBlocks::legacyHero($blocks);
        $content = LandingPageBlocks::legacyContent($blocks);

        foreach ([
            'blocks' => $blocks,
            'hero_badge' => $hero['hero_badge'],
            'hero_title' => $hero['hero_title'],
            'hero_excerpt' => RichText::normalizePlain($hero['hero_excerpt']),
            'intro_title' => $content['intro_title'],
            'intro_excerpt' => RichText::normalizePlain($content['intro_excerpt']),
            'body' => RichText::sanitize($content['body']),
            'cta_title' => $content['cta_title'],
            'cta_excerpt' => RichText::normalizePlain($content['cta_excerpt']),
            'cta_primary_label' => $content['cta_primary_label'],
            'cta_primary_url' => $content['cta_primary_url'],
            'cta_secondary_label' => $content['cta_secondary_label'],
            'cta_secondary_url' => $content['cta_secondary_url'],
            'faq_items' => LandingPageBlocks::legacyFaqItems($blocks),
            'visual_config' => LandingPageBlocks::legacyVisualConfig($blocks),
        ] as $field => $value) {
            $page->setAttribute($field, $value);
        }
    }

    private function source(SeoOptimizationPage $page): ?Model
    {
        $class = match ($page->owner_type) {
            'landing_page' => LandingPage::class,
            'tour' => Tour::class,
            'tour_category' => TourCategory::class,
            'destination' => Destination::class,
            'region' => Region::class,
            'service' => Service::class,
            'content_category' => ContentCategory::class,
            'blog_post' => BlogPost::class,
            default => null,
        };

        return $class && $page->owner_id !== null ? $class::query()->find($page->owner_id) : null;
    }

    /** @return array<string, mixed> */
    private function plain(string $label, ?int $max = null): array
    {
        return array_filter(['label' => $label, 'kind' => 'plain_text', 'max' => $max], fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private function richHtml(string $label): array
    {
        return ['label' => $label, 'kind' => 'rich_html'];
    }

    /** @return array<string, mixed> */
    private function manualHtml(string $label): array
    {
        return ['label' => $label, 'kind' => 'manual_html'];
    }

    /** @return array<string, mixed> */
    private function faq(string $label): array
    {
        return ['label' => $label, 'kind' => 'faq'];
    }

    /** @return array<string, mixed> */
    private function slug(string $label): array
    {
        return ['label' => $label, 'kind' => 'slug', 'max' => 180];
    }
}
