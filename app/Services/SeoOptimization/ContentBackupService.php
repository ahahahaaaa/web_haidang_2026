<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationBackup;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RuntimeException;

class ContentBackupService
{
    private const CONTENT_FIELDS = [
        'title', 'name', 'slug', 'excerpt', 'description', 'content', 'body', 'hero_badge',
        'hero_title', 'hero_excerpt', 'intro_title', 'intro_excerpt', 'cta_title', 'cta_excerpt',
        'cta_primary_label', 'cta_primary_url', 'cta_secondary_label', 'cta_secondary_url',
        'meta_title', 'meta_description', 'og_title',
        'og_description', 'canonical_url', 'robots_directive', 'cover_alt', 'faq_items',
        'geo_config', 'editor_mode', 'blocks', 'visual_config', 'home_config',
    ];

    public function create(SeoOptimizationProposal $proposal, SeoOptimizationPage $page, Model $source, User $actor): SeoOptimizationBackup
    {
        if ($existing = SeoOptimizationBackup::query()->where('proposal_id', $proposal->id)->first()) {
            $this->verify($existing);

            return $existing;
        }

        $content = collect(self::CONTENT_FIELDS)
            ->filter(fn (string $field): bool => array_key_exists($field, $source->getAttributes()))
            ->mapWithKeys(fn (string $field): array => [$field => $source->getAttribute($field)])
            ->all();
        $media = method_exists($source, 'getMedia')
            ? $source->getMedia('*')->map(fn ($item): array => [
                'id' => $item->getKey(),
                'collection' => $item->collection_name,
                'file_name' => $item->file_name,
                'name' => $item->name,
                'order_column' => $item->order_column,
                'custom_properties' => $item->custom_properties,
            ])->values()->all()
            : [];
        $publish = collect(['slug', 'status', 'is_active', 'published_at', 'canonical_url', 'robots_directive'])
            ->filter(fn (string $field): bool => array_key_exists($field, $source->getAttributes()))
            ->mapWithKeys(fn (string $field): array => [$field => $source->getAttribute($field)])
            ->all();
        $payload = [
            'page_id' => $page->id,
            'proposal_id' => $proposal->id,
            'source_version' => $proposal->source_version,
            'owner_type' => $page->owner_type,
            'owner_id' => (string) $page->owner_id,
            'content_snapshot' => $content,
            'media_snapshot' => $media,
            'publish_snapshot' => $publish,
        ];

        return SeoOptimizationBackup::query()->create([
            ...$payload,
            'created_by' => $actor->id,
            'checksum' => $this->checksum($payload),
        ]);
    }

    public function verify(SeoOptimizationBackup $backup): void
    {
        $payload = Arr::only($backup->getAttributes(), [
            'page_id', 'proposal_id', 'source_version', 'owner_type', 'owner_id',
        ]);
        $payload['content_snapshot'] = $backup->content_snapshot ?? [];
        $payload['media_snapshot'] = $backup->media_snapshot ?? [];
        $payload['publish_snapshot'] = $backup->publish_snapshot ?? [];

        if (! hash_equals((string) $backup->checksum, $this->checksum($payload))) {
            throw new RuntimeException('SEO optimization backup checksum mismatch.');
        }
    }

    private function checksum(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
