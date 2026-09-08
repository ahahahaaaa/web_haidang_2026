<?php

namespace App\Services\Cms;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class BlogAutomationReferenceFetcher
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchMany(array $urls, ?int $limit = null): array
    {
        $max = $limit ?: (int) config('blog_automation.max_reference_urls', 5);

        return collect($urls)
            ->filter(fn (mixed $url) => is_string($url) && trim($url) !== '')
            ->map(fn (string $url) => trim($url))
            ->unique()
            ->take($max)
            ->map(fn (string $url) => $this->fetch($url))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetch(string $url): ?array
    {
        try {
            $response = Http::accept('text/html,application/xhtml+xml')
                ->withUserAgent((string) config('blog_automation.user_agent'))
                ->connectTimeout((int) config('blog_automation.connect_timeout_seconds', 10))
                ->timeout((int) config('blog_automation.request_timeout_seconds', 25))
                ->retry(2, 500)
                ->get($url)
                ->throw();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        return $this->extractDocument($url, (string) $response->body());
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractDocument(string $url, string $html): ?array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $this->removeNodes($xpath, '//script|//style|//noscript|//template');

        $title = $this->firstNodeText($xpath, '//meta[@property="og:title"]/@content')
            ?: $this->firstNodeText($xpath, '//meta[@name="title"]/@content')
            ?: $this->firstNodeText($xpath, '//title');

        $description = $this->firstNodeText($xpath, '//meta[@name="description"]/@content')
            ?: $this->firstNodeText($xpath, '//meta[@property="og:description"]/@content');

        $canonical = $this->firstNodeText($xpath, '//link[@rel="canonical"]/@href') ?: $url;
        $contentRoot = $this->firstElement($xpath, '//article')
            ?: $this->firstElement($xpath, '//main')
            ?: $this->firstElement($xpath, '//body');

        if (! $contentRoot) {
            return null;
        }

        $headings = collect(iterator_to_array($xpath->query('.//h1|.//h2|.//h3', $contentRoot) ?: []))
            ->filter(fn (mixed $node) => $node instanceof DOMElement)
            ->map(fn (DOMElement $element) => $this->normalizeText($element->textContent))
            ->filter()
            ->take(12)
            ->values()
            ->all();

        $content = Str::limit(
            $this->normalizeText($contentRoot->textContent),
            (int) config('blog_automation.max_source_content_length', 12000),
            '',
        );

        if ($content === '') {
            return null;
        }

        return [
            'url' => $url,
            'canonical_url' => $canonical,
            'title' => $title ?: $url,
            'description' => $description,
            'headings' => $headings,
            'content' => $content,
        ];
    }

    protected function normalizeText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    protected function firstNodeText(DOMXPath $xpath, string $expression): ?string
    {
        $node = $xpath->query($expression)?->item(0);

        if (! $node) {
            return null;
        }

        return $this->normalizeText($node->textContent ?: $node->nodeValue);
    }

    protected function firstElement(DOMXPath $xpath, string $expression): ?DOMElement
    {
        $node = $xpath->query($expression)?->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    protected function removeNodes(DOMXPath $xpath, string $expression): void
    {
        $nodes = $xpath->query($expression);

        if (! $nodes) {
            return;
        }

        foreach (iterator_to_array($nodes) as $node) {
            $node->parentNode?->removeChild($node);
        }
    }
}
