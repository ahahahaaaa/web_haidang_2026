<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPage;
use App\Models\User;
use App\Services\Cms\SiteSettingsManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KeywordBriefResolver
{
    private const AUTOMATIC_ORIGINS = ['site_seo_keywords', 'server_keyword_fallback', 'server_keyword_profile'];

    public function __construct(
        private OptimizationBrief $briefs,
        private SiteSettingsManager $siteSettings,
    ) {}

    public function resolve(SeoOptimizationPage $page, User $actor): array
    {
        $existing = $page->keyword_brief ?? [];
        $existingPrimary = trim((string) ($existing['primary_keyword'] ?? ''));

        if ($existingPrimary !== '') {
            return $this->completeForAutomation($page, $actor, $this->keywordSettings());
        }

        $brief = $this->defaultBrief($page, $actor, $this->keywordSettings());

        if ($this->conflictingPage($page, $brief)) {
            return $brief;
        }

        return $this->persistDefault($page, $actor, $brief)['brief'];
    }

    public function completeAuditInput(SeoOptimizationPage $page, array $input): array
    {
        $entities = $this->terms($input['entities'] ?? []);
        $topics = $this->terms($input['required_topics'] ?? []);

        $input['entities'] = $entities !== []
            ? $entities
            : array_values(array_unique(array_filter([$page->title, 'Hải Đăng Travel'])));
        $input['required_topics'] = $topics !== [] ? $topics : $this->requiredTopics($page->page_type);

        return $input;
    }

    public function syncDefaults(Builder $query, User $actor): array
    {
        $settings = $this->keywordSettings();
        $owners = $this->keywordOwners();
        $result = ['updated' => 0, 'unchanged' => 0, 'manual_preserved' => 0, 'active_work_preserved' => 0, 'conflicts' => 0];

        $query
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($pages) use ($actor, $settings, &$owners, &$result): void {
                DB::transaction(function () use ($pages, $actor, $settings, &$owners, &$result): void {
                    $lockedPages = SeoOptimizationPage::query()
                        ->whereKey($pages->modelKeys())
                        ->withExists([
                            'tasks as has_active_tasks' => fn ($query) => $query->whereIn('status', ['queued', 'leased']),
                            'proposals as has_open_proposals' => fn ($query) => $query->whereIn('status', ['in_review', 'approved']),
                        ])
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($lockedPages as $page) {
                        if ($page->has_active_tasks || $page->has_open_proposals) {
                            $result['active_work_preserved']++;

                            continue;
                        }

                        $existing = $page->keyword_brief ?? [];
                        $existingPrimary = trim((string) ($existing['primary_keyword'] ?? ''));
                        $origin = (string) ($existing['origin'] ?? '');

                        if ($existingPrimary !== '' && ! in_array($origin, self::AUTOMATIC_ORIGINS, true)) {
                            $result['manual_preserved']++;

                            continue;
                        }

                        $brief = $this->defaultBrief($page, $actor, $settings);
                        $oldKey = $this->ownershipKey($existing);
                        $newKey = $this->ownershipKey($brief);

                        if ($oldKey !== null && ($owners[$oldKey] ?? null) === $page->id) {
                            unset($owners[$oldKey]);
                        }

                        if ($newKey !== null && isset($owners[$newKey]) && $owners[$newKey] !== $page->id) {
                            if ($oldKey !== null) {
                                $owners[$oldKey] = $page->id;
                            }
                            $result['conflicts']++;

                            continue;
                        }

                        $stored = $this->persistDefault($page, $actor, $brief);
                        if ($newKey !== null) {
                            $owners[$newKey] = $page->id;
                        }
                        $result[$stored['changed'] ? 'updated' : 'unchanged']++;
                    }
                });
            });

        return $result;
    }

    private function defaultBrief(SeoOptimizationPage $page, User $actor, array $settings): array
    {
        $existing = $page->keyword_brief ?? [];
        [$primary, $origin] = $this->defaultPrimary($page, $settings);
        $factSources = $existing['fact_sources'] ?? [];

        $input = [
            'primary_keyword' => $primary,
            'search_intent' => $this->intent($page->page_type),
            'secondary_keywords' => array_values(array_filter($settings['keywords'], fn (string $keyword): bool => ! $this->same($keyword, $primary))),
            'semantic_terms' => $this->semanticTerms($page->page_type),
            'entities' => array_values(array_unique(array_filter([$page->title, 'Hải Đăng Travel']))),
            'required_topics' => $this->requiredTopics($page->page_type),
            'required_internal_links' => [],
            'fact_sources' => [],
            'notes' => $origin === 'site_seo_keywords'
                ? 'Brief mặc định lấy từ bộ SEO keywords chính của website vì từ khóa khớp chính xác tiêu đề hoặc đường dẫn trang.'
                : 'Brief mặc định lấy tiêu đề trang làm từ khóa chính; bộ SEO keywords chính của website được dùng làm từ khóa phụ.',
        ];
        $brief = $this->briefs->validate($input, $actor, false, $origin);
        $brief['fact_sources'] = $factSources;
        $brief['keyword_role'] = 'OWNER';
        $brief['revision'] = $this->briefs->revision($brief);

        return $brief;
    }

    private function persistDefault(SeoOptimizationPage $page, User $actor, array $brief): array
    {
        $existing = $page->keyword_brief ?? [];
        $origin = (string) $brief['origin'];
        $primary = (string) $brief['primary_keyword'];

        $changed = ! $this->equivalent($existing, $brief);

        if ($changed) {
            $page->update(['keyword_brief' => $brief]);
            SeoOptimizationEvent::query()->create([
                'page_id' => $page->id,
                'actor_id' => $actor->id,
                'event' => 'brief.default_synced',
                'payload' => [
                    'origin' => $origin,
                    'primary_keyword' => $primary,
                    'revision' => $brief['revision'],
                ],
            ]);
        }

        return ['brief' => $changed ? $brief : $existing, 'changed' => $changed];
    }

    private function completeForAutomation(SeoOptimizationPage $page, User $actor, array $settings): array
    {
        $existing = $page->keyword_brief ?? [];
        $factSources = $existing['fact_sources'] ?? [];
        $origin = (string) ($existing['origin'] ?? 'server_keyword_profile');
        $input = $this->completeAuditInput($page, [
            ...$existing,
            'primary_keyword' => trim((string) $existing['primary_keyword']),
            'search_intent' => $existing['search_intent'] ?? $this->intent($page->page_type),
            'secondary_keywords' => $existing['secondary_keywords'] ?? array_values(array_filter(
                $settings['keywords'],
                fn (string $keyword): bool => ! $this->same($keyword, (string) $existing['primary_keyword']),
            )),
            'semantic_terms' => $existing['semantic_terms'] ?? $this->semanticTerms($page->page_type),
            'required_internal_links' => $existing['required_internal_links'] ?? [],
            'fact_sources' => [],
            'notes' => $existing['notes'] ?? 'Server bổ sung topic và entity mặc định; từ khóa chính do người dùng chọn được giữ nguyên.',
        ]);
        $brief = $this->briefs->validate($input, $actor, false, $origin);
        $brief['fact_sources'] = $factSources;
        $brief['keyword_role'] = $existing['keyword_role'] ?? 'OWNER';
        $brief['updated_by'] = $existing['updated_by'] ?? $brief['updated_by'];
        $brief['revision'] = $this->briefs->revision($brief);

        if (! $this->equivalent($existing, $brief)) {
            $page->update(['keyword_brief' => $brief]);
            SeoOptimizationEvent::query()->create([
                'page_id' => $page->id,
                'actor_id' => $actor->id,
                'event' => 'brief.automation_completed',
                'payload' => [
                    'origin' => $origin,
                    'primary_keyword' => $brief['primary_keyword'],
                    'revision' => $brief['revision'],
                ],
            ]);

            return $brief;
        }

        return $existing;
    }

    public function conflictingPage(SeoOptimizationPage $page, array $brief): ?SeoOptimizationPage
    {
        $primary = trim((string) ($brief['primary_keyword'] ?? ''));
        $intent = trim((string) ($brief['search_intent'] ?? ''));

        if ($primary === '' || $intent === '') {
            return null;
        }

        return SeoOptimizationPage::query()
            ->where('site_id', $page->site_id)
            ->where('locale', $page->locale)
            ->where('classification', 'INDEXABLE')
            ->whereKeyNot($page->getKey())
            ->get(['id', 'path', 'title', 'keyword_brief'])
            ->first(function (SeoOptimizationPage $candidate) use ($primary, $intent): bool {
                $candidateBrief = $candidate->keyword_brief ?? [];

                return $this->same((string) ($candidateBrief['primary_keyword'] ?? ''), $primary)
                    && $this->same((string) ($candidateBrief['search_intent'] ?? ''), $intent);
            });
    }

    private function keywordSettings(): array
    {
        $settings = $this->siteSettings->current();
        $keywords = (string) $settings->seo_keywords;

        return [
            'keywords' => array_values(array_unique(array_filter(array_map(
                fn (string $keyword): string => trim($keyword),
                preg_split('/[,;\r\n]+/u', $keywords) ?: [],
            )))),
            'site_names' => array_values(array_unique(array_filter([
                trim((string) $settings->site_name),
                trim((string) $settings->company_name),
            ]))),
        ];
    }

    private function keywordOwners(): array
    {
        return SeoOptimizationPage::query()
            ->where('site_id', config('seo_optimization.site_id'))
            ->where('locale', config('seo_optimization.locale', 'vi'))
            ->where('classification', 'INDEXABLE')
            ->get(['id', 'keyword_brief'])
            ->reduce(function (array $owners, SeoOptimizationPage $page): array {
                $key = $this->ownershipKey($page->keyword_brief ?? []);

                if ($key !== null) {
                    $owners[$key] ??= $page->id;
                }

                return $owners;
            }, []);
    }

    private function ownershipKey(array $brief): ?string
    {
        $primary = $this->normalize((string) ($brief['primary_keyword'] ?? ''));
        $intent = $this->normalize((string) ($brief['search_intent'] ?? ''));

        return $primary !== '' && $intent !== '' ? $primary.'|'.$intent : null;
    }

    private function defaultPrimary(SeoOptimizationPage $page, array $settings): array
    {
        $targets = [trim((string) $page->title), trim((string) $page->path, '/')];

        if ($page->page_type === 'home') {
            $targets = [...$targets, ...$settings['site_names']];
        }

        $normalizedTargets = array_map($this->normalize(...), $targets);
        $matches = [];
        foreach ($settings['keywords'] as $keyword) {
            $normalizedKeyword = $this->normalize($keyword);
            if (in_array($normalizedKeyword, $normalizedTargets, true)) {
                $matches[$normalizedKeyword] ??= $keyword;
            }
        }

        if (count($matches) === 1) {
            return [array_values($matches)[0], 'site_seo_keywords'];
        }

        $fallback = trim((string) $page->title);
        if ($fallback === '') {
            $fallback = (string) Str::of(trim((string) $page->path, '/'))
                ->replace(['-', '/'], ' ')
                ->squish();
        }

        return [$fallback, 'server_keyword_fallback'];
    }

    private function equivalent(array $existing, array $brief): bool
    {
        unset($existing['revision'], $existing['updated_by'], $brief['revision'], $brief['updated_by']);

        return $existing === $brief;
    }

    private function normalize(string $value): string
    {
        return (string) Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    private function intent(string $type): string
    {
        return match ($type) {
            'tour' => 'TRANSACTIONAL',
            'service' => 'LOCAL_SERVICE',
            'tour_category', 'destination', 'country', 'region', 'service_category' => 'COMMERCIAL_INVESTIGATION',
            'contact', 'about', 'home' => 'NAVIGATIONAL',
            default => 'INFORMATIONAL',
        };
    }

    private function requiredTopics(string $type): array
    {
        return match ($type) {
            'tour' => ['điểm nổi bật', 'lịch trình', 'dịch vụ bao gồm', 'chi phí', 'câu hỏi thường gặp'],
            'service' => ['phạm vi dịch vụ', 'quy trình', 'chi phí', 'câu hỏi thường gặp'],
            'blog_post' => ['tóm tắt', 'kinh nghiệm', 'lưu ý', 'câu hỏi thường gặp'],
            'tour_category', 'destination', 'country', 'region' => ['lựa chọn tour', 'kinh nghiệm', 'điểm đến', 'câu hỏi thường gặp'],
            'service_category' => ['dịch vụ', 'quy trình', 'liên hệ', 'câu hỏi thường gặp'],
            default => ['thông tin chính', 'lựa chọn phù hợp', 'liên hệ', 'câu hỏi thường gặp'],
        };
    }

    private function semanticTerms(string $type): array
    {
        return match ($type) {
            'tour', 'tour_category', 'destination', 'country', 'region' => ['hành trình', 'điểm đến', 'khởi hành', 'tư vấn tour'],
            'service', 'service_category' => ['tư vấn', 'quy trình', 'hỗ trợ', 'liên hệ'],
            'blog_post', 'blog_category' => ['du lịch', 'kinh nghiệm', 'hướng dẫn', 'lưu ý'],
            default => ['du lịch', 'Hải Đăng Travel', 'tư vấn'],
        };
    }

    private function terms(mixed $terms): array
    {
        if (! is_array($terms)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($term): string => trim((string) $term),
            $terms,
        ), fn (string $term): bool => $term !== '')));
    }

    private function same(string $left, string $right): bool
    {
        return Str::lower(trim($left)) === Str::lower(trim($right));
    }
}
