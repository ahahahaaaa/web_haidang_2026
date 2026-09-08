<?php

namespace App\Jobs\Seo;

use App\Models\SeoPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ValidateSeoPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $pageId)
    {
        $this->onQueue(config('seo_ai.queue', 'seo'));
    }

    public function handle(): void
    {
        $page = SeoPage::query()->withCount('outgoingLinks')->findOrFail($this->pageId);

        $contentText = strip_tags(Str::markdown($page->content ?? ''));
        $wordCount = str_word_count($contentText);

        $minWordCount = match ($page->page_type) {
            'blog' => (int) config('seo_ai.quality_gates.blog_word_count_min', 1200),
            'service' => (int) config('seo_ai.quality_gates.service_word_count_min', 900),
            default => 500,
        };

        $errors = [];

        if (blank($page->h1)) $errors[] = 'Missing H1';
        if (blank($page->meta_title)) $errors[] = 'Missing meta title';
        if (blank($page->meta_description)) $errors[] = 'Missing meta description';
        if (empty($page->schema)) $errors[] = 'Missing schema';
        if ($wordCount < $minWordCount) $errors[] = 'Word count below threshold';
        if ($page->outgoing_links_count < 1) $errors[] = 'No internal links';

        $page->qa_report = [
            'word_count' => $wordCount,
            'min_word_count' => $minWordCount,
            'outgoing_links_count' => $page->outgoing_links_count,
            'errors' => $errors,
            'status' => empty($errors) ? 'pass' : 'fail',
        ];

        $page->status = empty($errors) ? 'pending_review' : 'qa_failed';
        $page->save();
    }
}
