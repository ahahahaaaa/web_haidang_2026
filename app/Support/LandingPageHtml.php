<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class LandingPageHtml
{
    public static function render(?string $html, bool $allowPrimaryHeading = false): string
    {
        $html = trim((string) $html);

        if ($html === '' || ! preg_match('/<!doctype\b|<\/?(?:html|head|body|title|meta|base|h1)\b|<link\b[^>]*canonical/i', $html)) {
            return $html;
        }

        $document = self::loadDocument($html);
        $hasPrimaryHeading = ! $allowPrimaryHeading;
        self::normalize($document, $document, $hasPrimaryHeading);

        $fragment = '';
        foreach ($document->childNodes as $child) {
            $fragment .= $document->saveHTML($child);
        }

        return trim($fragment);
    }

    public static function hasPrimaryHeading(?string $html): bool
    {
        return preg_match('/<h1(?:\s|>)/i', (string) $html)
            && self::loadDocument((string) $html)->getElementsByTagName('h1')->length > 0;
    }

    protected static function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }

    protected static function normalize(DOMNode $node, DOMDocument $document, bool &$hasPrimaryHeading): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, ['meta', 'base'], true)
                    || ($tag === 'title' && ! ($child->parentNode instanceof DOMElement && strtolower($child->parentNode->tagName) === 'svg'))
                    || ($tag === 'link' && in_array('canonical', preg_split('/\s+/', strtolower($child->getAttribute('rel'))), true))) {
                    $node->removeChild($child);

                    continue;
                }

                if ($tag === 'h1') {
                    if ($hasPrimaryHeading) {
                        $heading = $document->createElement('h2');
                        foreach ($child->attributes as $attribute) {
                            $heading->setAttribute($attribute->nodeName, $attribute->nodeValue);
                        }
                        while ($child->firstChild) {
                            $heading->appendChild($child->firstChild);
                        }
                        $node->replaceChild($heading, $child);
                        $child = $heading;
                    }
                    $hasPrimaryHeading = true;
                }

                self::normalize($child, $document, $hasPrimaryHeading);

                if (in_array($tag, ['html', 'head', 'body'], true)) {
                    if ($tag === 'body' && $child->hasAttributes()) {
                        $wrapper = $document->createElement('div');
                        foreach ($child->attributes as $attribute) {
                            $wrapper->setAttribute($attribute->nodeName, $attribute->nodeValue);
                        }
                        while ($child->firstChild) {
                            $wrapper->appendChild($child->firstChild);
                        }
                        $node->replaceChild($wrapper, $child);
                    } else {
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                    }
                }
            }
        }
    }
}
