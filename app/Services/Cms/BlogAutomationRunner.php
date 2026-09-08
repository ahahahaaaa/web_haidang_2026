<?php

namespace App\Services\Cms;

use App\Models\User;
use App\Services\OpenAI\OpenAIResponsesClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Src\Domains\Cms\Models\BlogPost;

class BlogAutomationRunner
{
    public function __construct(
        protected BlogAutomationReferenceFetcher $fetcher,
        protected BlogPostManager $posts,
        protected OpenAIResponsesClient $client,
        protected SiteSettingsManager $siteSettings,
    ) {}

    public function run(array $payload, ?User $actor = null): BlogPost
    {
        $references = $this->fetcher->fetchMany(
            urls: $payload['reference_urls'] ?? [],
            limit: $payload['max_references'] ?? null,
        );

        if ($references === []) {
            throw new RuntimeException('Không thể đọc nội dung từ các link tham chiếu đã cung cấp.');
        }

        $existingPost = ! empty($payload['target_post_id'])
            ? BlogPost::query()->findOrFail((int) $payload['target_post_id'])
            : null;

        $generatedPayload = $this->generateDraft($payload, $references, $existingPost);

        $manualOverrides = Arr::only($payload, [
            'title',
            'slug',
            'excerpt',
            'content',
            'status',
            'content_category_id',
            'content_category_slug',
            'author_name',
            'published_at',
            'is_featured',
            'sort_order',
            'cover_alt',
            'meta_title',
            'meta_description',
            'og_title',
            'og_description',
            'canonical_url',
            'robots_directive',
            'schema',
        ]);

        return $this->posts->save(
            payload: array_merge($generatedPayload, $manualOverrides),
            post: $existingPost,
            actor: $actor,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $references
     * @return array<string, mixed>
     */
    protected function generateDraft(array $payload, array $references, ?BlogPost $existingPost): array
    {
        if (blank(config('services.openai.api_key'))) {
            throw new RuntimeException('Thiếu cấu hình OPENAI_API_KEY cho luồng blog automation.');
        }

        $site = $this->siteSettings->current();
        $response = $this->client->respond(
            instructions: implode("\n", [
                'Return valid JSON only.',
                'Use fully accented Vietnamese.',
                'Rewrite and synthesize an original article from the references without copying source sentences.',
                'Do not invent awards, certifications, addresses, exact project counts, or guarantees not grounded in the sources or business context.',
                'The "content" field must be HTML using only h2, h3, p, ul, li, strong, em, blockquote, and a tags.',
                'Keep the article practical, scannable, and suitable for a construction-company blog.',
            ]),
            input: $this->buildPromptInput($payload, $references, $existingPost, [
                'company_name' => $site->company_name ?: $site->site_name,
                'site_name' => $site->site_name,
                'site_description' => $site->site_description,
                'about_summary' => $site->about_summary,
            ]),
        );

        $decoded = $this->decodeJsonPayload($this->client->outputText($response));

        if (! is_array($decoded) || blank($decoded['title'] ?? null) || blank($decoded['content'] ?? null)) {
            throw new RuntimeException('OpenAI trả về payload blog automation không hợp lệ.');
        }

        return [
            'title' => trim((string) $decoded['title']),
            'slug' => trim((string) ($decoded['slug'] ?? '')),
            'excerpt' => $decoded['excerpt'] ?? null,
            'content' => $decoded['content'] ?? null,
            'meta_title' => $decoded['meta_title'] ?? null,
            'meta_description' => $decoded['meta_description'] ?? null,
            'og_title' => $decoded['og_title'] ?? null,
            'og_description' => $decoded['og_description'] ?? null,
            'author_name' => $decoded['author_name'] ?? null,
            'cover_alt' => $decoded['cover_alt'] ?? null,
            'robots_directive' => $decoded['robots_directive'] ?? null,
            'status' => $decoded['status'] ?? config('blog_automation.default_status', 'draft'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $references
     * @param  array<string, mixed>  $siteContext
     */
    protected function buildPromptInput(array $payload, array $references, ?BlogPost $existingPost, array $siteContext): string
    {
        return json_encode([
            'task' => 'Create or refresh a construction-company blog post from competitor references.',
            'site_context' => $siteContext,
            'desired_post' => [
                'title' => $payload['title'] ?? null,
                'slug' => $payload['slug'] ?? null,
                'status' => $payload['status'] ?? config('blog_automation.default_status', 'draft'),
                'content_category_slug' => $payload['content_category_slug'] ?? null,
                'content_category_id' => $payload['content_category_id'] ?? null,
                'author_name' => $payload['author_name'] ?? null,
                'additional_instructions' => $payload['additional_instructions'] ?? null,
            ],
            'existing_post' => $existingPost ? [
                'id' => $existingPost->getKey(),
                'title' => $existingPost->title,
                'slug' => $existingPost->slug,
                'excerpt' => $existingPost->excerpt,
                'content' => Str::limit((string) $existingPost->content, 6000, ''),
                'status' => $existingPost->status,
                'meta_title' => $existingPost->meta_title,
                'meta_description' => $existingPost->meta_description,
            ] : null,
            'reference_articles' => $references,
            'output_contract' => [
                'title' => 'string',
                'slug' => 'string',
                'excerpt' => 'string',
                'content' => 'html string',
                'meta_title' => 'string',
                'meta_description' => 'string',
                'og_title' => 'string',
                'og_description' => 'string',
                'author_name' => 'string',
                'cover_alt' => 'string',
                'robots_directive' => 'string',
                'status' => 'string',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonPayload(string $text): array
    {
        $trimmed = trim($text);

        if (preg_match('/\{.*\}/s', $trimmed, $matches) !== 1) {
            throw new RuntimeException('Không tìm thấy JSON hợp lệ trong phản hồi OpenAI.');
        }

        $decoded = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
