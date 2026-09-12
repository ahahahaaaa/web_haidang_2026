<?php

namespace App\Services\SeoOptimization;

use App\Models\User;
use App\Support\RichText;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OptimizationBrief
{
    public const INTENTS = ['INFORMATIONAL', 'COMMERCIAL_INVESTIGATION', 'TRANSACTIONAL', 'NAVIGATIONAL', 'LOCAL_SERVICE'];

    public function validate(array $input, User $user, bool $humanVerified = true, ?string $origin = null): array
    {
        $input['search_intent'] = str_replace([' ', '/'], '_', Str::upper($input['search_intent'] ?? 'INFORMATIONAL'));
        $rules = [
            'primary_keyword' => ['required', 'string', 'max:200'],
            'search_intent' => ['required', 'in:'.implode(',', self::INTENTS)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'fact_sources' => ['sometimes', 'array', 'max:50'],
        ];
        foreach (['secondary_keywords', 'semantic_terms', 'entities', 'required_topics', 'required_internal_links'] as $key) {
            $rules[$key] = ['sometimes', 'array', 'max:50'];
            $rules[$key.'.*'] = ['string', 'max:500'];
        }
        $brief = Validator::make($input, $rules)->validate();
        $brief['primary_keyword'] = RichText::normalizePlain($brief['primary_keyword']);
        foreach (['secondary_keywords', 'semantic_terms', 'entities', 'required_topics', 'required_internal_links'] as $key) {
            $brief[$key] = array_values(array_unique(array_filter(array_map(fn ($v) => trim(RichText::normalizePlain($v)), $brief[$key] ?? []))));
        }
        $sources = [];
        foreach ($brief['fact_sources'] ?? [] as $source) {
            if (is_string($source)) {
                $sources[] = ['id' => 'source-'.Str::random(10), 'label' => Str::limit(RichText::normalizePlain($source), 500), 'quote' => '', 'verified_by' => null];

                continue;
            }
            $source = Validator::make((array) $source, [
                'id' => ['sometimes', 'string', 'max:100'], 'label' => ['required', 'string', 'max:500'],
                'quote' => ['required', 'string', 'max:10000'], 'url' => ['nullable', 'url:http,https', 'max:2000'],
            ])->validate();
            $source['id'] ??= 'source-'.substr(hash('sha256', json_encode($source)), 0, 20);
            $source['quote'] = RichText::normalizePlain($source['quote']);
            $source['verified_by'] = $humanVerified ? $user->id : null;
            $source['verified_at'] = $humanVerified ? now()->toIso8601String() : null;
            $sources[] = $source;
        }
        $brief['fact_sources'] = $sources;
        Validator::make(['ids' => array_column($sources, 'id')], ['ids.*' => ['distinct']])->validate();
        $brief['origin'] = $origin ?? ($humanVerified ? 'cms_review_brief' : 'remote_import');
        $brief['updated_by'] = $user->id;
        $brief['revision'] = $this->revision($brief);

        return $brief;
    }

    public function revision(array $brief): string
    {
        unset($brief['revision']);

        return 'brief-'.hash('sha256', json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
