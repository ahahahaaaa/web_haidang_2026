<?php

namespace App\Actions\SeoOptimization;

use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationOutbox;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\OptimizationBrief;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BulkUpdateKeywordBriefs
{
    private const SCALAR_FIELDS = ['primary_keyword', 'search_intent', 'notes'];

    private const LIST_FIELDS = [
        'secondary_keywords',
        'semantic_terms',
        'entities',
        'required_topics',
        'required_internal_links',
    ];

    public function __construct(
        private OptimizationAccess $access,
        private OptimizationBrief $briefs,
    ) {}

    /**
     * Apply sparse changes to the pages in an already authorized server-side query.
     *
     * @return array{selected:int,updated:int,unchanged:int,active_work_preserved:int,incomplete:int,conflicts:int}
     */
    public function handle(Builder $query, array $changes, User $user): array
    {
        $this->humanOnly();
        $this->access->authorize($user, 'propose');
        $changes = $this->normalizeChanges($changes);
        if ($changes === []) {
            throw ValidationException::withMessages([
                'bulkBrief' => 'Nhập ít nhất một trường brief cần bổ sung.',
            ]);
        }

        $result = [
            'selected' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'active_work_preserved' => 0,
            'incomplete' => 0,
            'conflicts' => 0,
        ];

        (clone $query)
            ->reorder()
            ->select('seo_optimization_pages.id')
            ->orderBy('seo_optimization_pages.id')
            ->chunkById(200, function (Collection $rows) use ($changes, $user, &$result): void {
                $ids = $rows->pluck('id')->map(fn ($id): string => (string) $id)->all();
                $result['selected'] += count($ids);

                DB::transaction(function () use ($ids, $changes, $user, &$result): void {
                    $scopePairs = SeoOptimizationPage::query()
                        ->whereKey($ids)
                        ->get(['site_id', 'locale'])
                        ->map(fn (SeoOptimizationPage $page): string => $page->site_id."\0".$page->locale)
                        ->unique()
                        ->map(fn (string $scope): array => array_combine(['site_id', 'locale'], explode("\0", $scope, 2)))
                        ->values();
                    if ($scopePairs->isEmpty()) {
                        return;
                    }

                    $lockedPages = SeoOptimizationPage::query()
                        ->where(function (Builder $query) use ($scopePairs): void {
                            foreach ($scopePairs as $scope) {
                                $query->orWhere(function (Builder $query) use ($scope): void {
                                    $query->where('site_id', $scope['site_id'])->where('locale', $scope['locale']);
                                });
                            }
                        })
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $selectedIds = array_fill_keys($ids, true);
                    $selectedPages = $lockedPages->filter(
                        fn (SeoOptimizationPage $page): bool => isset($selectedIds[(string) $page->id]),
                    );
                    $activePageIds = SeoOptimizationTask::query()
                        ->whereIn('page_id', $ids)
                        ->whereIn('status', ['queued', ...SeoOptimizationTask::PROCESSING_STATUSES])
                        ->pluck('page_id')
                        ->mapWithKeys(fn ($id): array => [(string) $id => true]);
                    $owners = $lockedPages->reduce(function (array $owners, SeoOptimizationPage $page): array {
                        $key = $this->ownershipKey($page->keyword_brief ?? []);
                        if ($key !== null) {
                            $owners[$key] ??= (string) $page->id;
                        }

                        return $owners;
                    }, []);

                    foreach ($selectedPages as $page) {
                        $this->access->authorize($user, 'propose', $page);
                        if ($activePageIds->has((string) $page->id)) {
                            $result['active_work_preserved']++;

                            continue;
                        }

                        $existing = is_array($page->keyword_brief) ? $page->keyword_brief : [];
                        $input = $this->merge($existing, $changes);
                        if (blank($input['primary_keyword'] ?? null) || blank($input['search_intent'] ?? null)) {
                            $result['incomplete']++;

                            continue;
                        }

                        $brief = $this->briefs->validate($input, $user, true, 'cms_review_brief');
                        $brief['keyword_role'] = $existing['keyword_role'] ?? 'OWNER';
                        $brief['revision'] = $this->briefs->revision($brief);

                        if ($this->contentSignature($existing) === $this->contentSignature($brief)) {
                            $result['unchanged']++;

                            continue;
                        }

                        $oldKey = $this->ownershipKey($existing);
                        $newKey = $this->ownershipKey($brief);
                        if ($newKey !== null && isset($owners[$newKey]) && $owners[$newKey] !== (string) $page->id) {
                            $result['conflicts']++;

                            continue;
                        }

                        if ($oldKey !== null && ($owners[$oldKey] ?? null) === (string) $page->id) {
                            unset($owners[$oldKey]);
                        }
                        if ($newKey !== null) {
                            $owners[$newKey] = (string) $page->id;
                        }

                        $page->update(['keyword_brief' => $brief]);
                        SeoOptimizationProposal::query()
                            ->where('page_id', $page->id)
                            ->whereIn('status', ['in_review', 'approved'])
                            ->update(['status' => 'stale', 'approved_at' => null, 'approved_by' => null]);
                        SeoOptimizationEvent::query()->create([
                            'page_id' => $page->id,
                            'actor_id' => $user->id,
                            'event' => 'brief.saved',
                            'payload' => [
                                'revision' => $brief['revision'],
                                'mode' => 'bulk',
                                'changed_fields' => array_keys($changes),
                            ],
                        ]);
                        SeoOptimizationOutbox::query()->firstOrCreate([
                            'event_key' => 'brief:'.$page->id.':'.$brief['revision'],
                        ], [
                            'destination' => '15_KEYWORD_SET',
                            'payload' => ['page_id' => $page->id, 'brief' => $brief, 'status' => 'REVIEW'],
                            'status' => 'pending',
                        ]);
                        $result['updated']++;
                    }
                });
            }, 'seo_optimization_pages.id', 'id');

        return $result;
    }

    private function normalizeChanges(array $changes): array
    {
        $normalized = [];
        foreach (self::SCALAR_FIELDS as $field) {
            if (isset($changes[$field]) && is_string($changes[$field]) && trim($changes[$field]) !== '') {
                $normalized[$field] = trim($changes[$field]);
            }
        }
        foreach (self::LIST_FIELDS as $field) {
            if (! isset($changes[$field]) || ! is_array($changes[$field])) {
                continue;
            }
            $items = array_values(array_unique(array_filter(array_map(
                fn ($item): string => is_string($item) ? trim($item) : '',
                $changes[$field],
            ))));
            if ($items !== []) {
                $normalized[$field] = $items;
            }
        }

        return $normalized;
    }

    private function merge(array $existing, array $changes): array
    {
        $merged = $existing;
        foreach (self::SCALAR_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $merged[$field] = $changes[$field];
            }
        }
        foreach (self::LIST_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $current = is_array($existing[$field] ?? null) ? $existing[$field] : [];
                $merged[$field] = array_values(array_unique([...$current, ...$changes[$field]]));
            }
        }
        $merged['fact_sources'] = is_array($existing['fact_sources'] ?? null) ? $existing['fact_sources'] : [];

        return $merged;
    }

    private function ownershipKey(array $brief): ?string
    {
        $primary = Str::lower(trim((string) ($brief['primary_keyword'] ?? '')));
        $intent = Str::lower(trim((string) ($brief['search_intent'] ?? '')));

        return $primary !== '' && $intent !== '' ? $primary.'|'.$intent : null;
    }

    private function contentSignature(array $brief): string
    {
        return hash('sha256', json_encode(collect($brief)->only([
            ...self::SCALAR_FIELDS,
            ...self::LIST_FIELDS,
            'fact_sources',
            'keyword_role',
        ])->all(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function humanOnly(): void
    {
        abort_if(request()->attributes->has('seo_optimization_credential'), 403, 'MCP không được sửa brief hàng loạt trong CMS.');
    }
}
