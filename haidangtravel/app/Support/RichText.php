<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RichText
{
    /**
     * @var array<int, string>
     */
    protected const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'code',
        'div',
        'em',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'img',
        'li',
        'ol',
        'p',
        'pre',
        's',
        'span',
        'strong',
        'u',
        'ul',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    protected const ALLOWED_ATTRIBUTES = [
        '*' => ['class', 'style'],
        'a' => ['href', 'rel', 'target', 'title'],
        'img' => ['alt', 'data-media-id', 'height', 'loading', 'src', 'title', 'width'],
    ];

    /**
     * @var array<int, string>
     */
    protected const ALLOWED_STYLE_PROPERTIES = [
        'aspect-ratio',
        'border-radius',
        'color',
        'clear',
        'display',
        'float',
        'height',
        'margin',
        'margin-bottom',
        'margin-left',
        'margin-right',
        'margin-top',
        'max-height',
        'max-width',
        'min-height',
        'min-width',
        'object-fit',
        'text-align',
        'width',
    ];

    public static function normalizePlain(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (! Str::contains($value, '<')) {
            $value = str_replace(["\r\n", "\r"], "\n", html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $value = str_replace("\xc2\xa0", ' ', $value);

            return trim((string) preg_replace("/\n{3,}/", "\n\n", $value));
        }

        $value = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/(p|div|h[1-6]|li|blockquote|pre|ul|ol)>/i', "\n", $value) ?? $value;
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace("/[ \t]+\n/", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

        return trim($value);
    }

    public static function render(?string $value): HtmlString
    {
        return new HtmlString(static::sanitize($value));
    }

    public static function renderInline(?string $value): HtmlString
    {
        return new HtmlString(static::sanitizeInline($value));
    }

    public static function sanitize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (! Str::contains($value, '<')) {
            return static::plainTextToHtml($value);
        }

        $dom = static::loadDocument($value);
        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        static::sanitizeNode($body);

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return trim($html);
    }

    public static function sanitizeInline(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (! Str::contains($value, '<')) {
            return static::plainTextToInlineHtml($value);
        }

        $dom = static::loadDocument($value);
        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        static::sanitizeNode($body);

        $html = static::collectInlineHtml($body, $dom);
        $html = preg_replace('/(?:<br>\s*){3,}/i', '<br><br>', $html) ?? $html;
        $html = preg_replace('/^(?:<br>\s*)+|(?:<br>\s*)+$/i', '', $html) ?? $html;

        return trim($html);
    }

    protected static function plainTextToHtml(string $value): string
    {
        return collect(preg_split('/\r\n\r\n|\r\r|\n\n/', trim($value)) ?: [])
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    protected static function plainTextToInlineHtml(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

        return nl2br(e(trim($value)), false);
    }

    protected static function loadDocument(string $html): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        $previous = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (iterator_to_array($dom->childNodes) as $child) {
            if ($child->nodeType === XML_PI_NODE) {
                $dom->removeChild($child);
            }
        }

        return $dom;
    }

    protected static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            static::sanitizeNode($child);
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['body', 'html'], true)) {
            return;
        }

        if (! in_array($tag, static::ALLOWED_TAGS, true)) {
            static::unwrapNode($node);

            return;
        }

        static::sanitizeAttributes($node, $tag);

        if ($tag === 'a' && $node->hasAttribute('href')) {
            $href = static::normalizeAnchorHref($node->getAttribute('href'));

            if ($href === null) {
                $node->removeAttribute('href');
            } else {
                $node->setAttribute('href', $href);
            }
        }

        if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'img' && (! $node->hasAttribute('src') || ! static::isSafeUrl($node->getAttribute('src')))) {
            $node->parentNode?->removeChild($node);
        }
    }

    protected static function sanitizeAttributes(DOMElement $node, string $tag): void
    {
        $allowed = array_merge(
            static::ALLOWED_ATTRIBUTES['*'],
            static::ALLOWED_ATTRIBUTES[$tag] ?? [],
        );

        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowed, true)) {
                $node->removeAttribute($attribute->nodeName);

                continue;
            }

            $value = trim($attribute->nodeValue ?? '');

            if ($value === '') {
                $node->removeAttribute($attribute->nodeName);

                continue;
            }

            if ($name === 'class') {
                $value = static::sanitizeClassList($value);
            }

            if ($name === 'style') {
                $value = static::sanitizeStyle($value);
            }

            if ($name === 'target' && ! in_array($value, ['_blank', '_self', '_parent', '_top'], true)) {
                $value = '';
            }

            if ($name === 'loading' && ! in_array($value, ['eager', 'lazy'], true)) {
                $value = '';
            }

            if (in_array($name, ['width', 'height', 'data-media-id'], true) && ! preg_match('/^\d+$/', $value)) {
                $value = '';
            }

            if ($value === '') {
                $node->removeAttribute($attribute->nodeName);

                continue;
            }

            $node->setAttribute($attribute->nodeName, $value);
        }
    }

    protected static function sanitizeClassList(string $value): string
    {
        return collect(preg_split('/\s+/', $value) ?: [])
            ->filter(fn (?string $class) => is_string($class) && preg_match('/^[A-Za-z0-9:_-]+$/', $class))
            ->unique()
            ->implode(' ');
    }

    protected static function sanitizeStyle(string $value): string
    {
        $declarations = collect(explode(';', $value))
            ->map(fn (string $declaration) => trim($declaration))
            ->filter();

        $safe = [];

        foreach ($declarations as $declaration) {
            [$property, $styleValue] = array_pad(explode(':', $declaration, 2), 2, null);

            $property = Str::of((string) $property)->trim()->lower()->toString();
            $styleValue = trim((string) $styleValue);

            if (
                $property === '' ||
                $styleValue === '' ||
                ! in_array($property, static::ALLOWED_STYLE_PROPERTIES, true) ||
                preg_match('/expression|javascript:|url\s*\(/i', $styleValue) ||
                ! preg_match('/^[#%(),.\/0-9A-Za-z\s:_-]+$/u', $styleValue)
            ) {
                continue;
            }

            $safe[] = $property.': '.$styleValue;
        }

        return implode('; ', $safe);
    }

    protected static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (Str::startsWith($url, ['/', '#'])) {
            return true;
        }

        return (bool) preg_match('/^https?:\/\//i', $url);
    }

    protected static function normalizeAnchorHref(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, '//')) {
            $url = 'https:'.$url;
        }

        if (! static::isSafeUrl($url)) {
            if (preg_match('/\s/u', $url)) {
                return null;
            }

            $candidate = 'https://'.$url;

            if (! filter_var($candidate, FILTER_VALIDATE_URL)) {
                return null;
            }

            $url = $candidate;
        }

        return static::isSafeUrl($url) ? $url : null;
    }

    protected static function unwrapNode(DOMElement $node): void
    {
        $parent = $node->parentNode;

        if (! $parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    protected static function collectInlineHtml(DOMNode $node, DOMDocument $dom): string
    {
        $html = '';

        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                $html .= e($child->wholeText);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['span', 'strong', 'em', 'u', 's', 'a', 'code', 'br'], true)) {
                $html .= $dom->saveHTML($child);

                continue;
            }

            if (in_array($tag, ['p', 'div', 'blockquote', 'li', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'], true)) {
                $content = trim(static::collectInlineHtml($child, $dom));

                if ($content !== '') {
                    $html .= $content.'<br>';
                }

                continue;
            }

            if (in_array($tag, ['ul', 'ol'], true)) {
                $html .= static::collectInlineHtml($child, $dom);

                continue;
            }

            if ($tag === 'hr') {
                $html .= '<br>';
            }
        }

        return trim($html);
    }
}
