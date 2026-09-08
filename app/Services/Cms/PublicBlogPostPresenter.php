<?php

namespace App\Services\Cms;

use App\Support\RichText;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\BlogPost;

class PublicBlogPostPresenter
{
    /**
     * @return array{renderedContent: HtmlString, tocItems: array<int, array{id: string, label: string}>}
     */
    public function present(BlogPost $post): array
    {
        [$renderedContent, $tocItems] = $this->renderContentWithTableOfContents($post);

        return [
            'renderedContent' => new HtmlString($renderedContent),
            'tocItems' => $tocItems,
        ];
    }

    /**
     * @return array{0: string, 1: array<int, array{id: string, label: string}>}
     */
    protected function renderContentWithTableOfContents(BlogPost $post): array
    {
        $html = RichText::sanitize($post->content);

        if ($html === '') {
            return ['', []];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return [$html, []];
        }

        $xpath = new DOMXPath($document);
        $tocItems = [];
        $usedIds = [];

        foreach (iterator_to_array($xpath->query('.//h2', $body) ?: []) as $index => $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', html_entity_decode($heading->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

            if ($label === '') {
                continue;
            }

            $baseId = Str::slug($label);
            $baseId = $baseId !== '' ? $baseId : 'muc-'.($index + 1);
            $id = $baseId;
            $suffix = 2;

            while (in_array($id, $usedIds, true)) {
                $id = $baseId.'-'.$suffix;
                $suffix++;
            }

            $usedIds[] = $id;
            $heading->setAttribute('id', $id);
            $tocItems[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        $renderedContent = '';

        foreach ($body->childNodes as $child) {
            $renderedContent .= $document->saveHTML($child);
        }

        return [trim($renderedContent), $tocItems];
    }
}
