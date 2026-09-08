<?php

namespace App\Support;

class GoogleMapsEmbedUrl
{
    public static function normalize(mixed $value): ?string
    {
        $value = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5));

        if ($value === '') {
            return null;
        }

        if (preg_match('/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1/i', $value, $matches) === 1) {
            $value = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5));
        }

        if (! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $value = 'https://'.$value;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || ! self::isGoogleMapsHost((string) ($parts['host'] ?? ''))) {
            return $value;
        }

        $path = (string) ($parts['path'] ?? '');
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (str_contains($path, '/maps/embed') || ($query['output'] ?? null) === 'embed') {
            return $value;
        }

        $mapQuery = self::queryFromPath($path) ?: trim((string) ($query['q'] ?? ''));

        if ($mapQuery === '' && preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $value, $matches) === 1) {
            $mapQuery = $matches[1].','.$matches[2];
        }

        if ($mapQuery === '') {
            return $value;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($mapQuery).'&output=embed';
    }

    public static function isEmbeddable(mixed $value): bool
    {
        $value = self::normalize($value);

        if ($value === null) {
            return true;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || ! self::isGoogleMapsHost((string) ($parts['host'] ?? ''))) {
            return false;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        return str_contains((string) ($parts['path'] ?? ''), '/maps/embed')
            || ($query['output'] ?? null) === 'embed';
    }

    protected static function queryFromPath(string $path): string
    {
        if (preg_match('~/maps/(?:search|place)/([^/?#]+)~', $path, $matches) !== 1) {
            return '';
        }

        return trim(str_replace('+', ' ', rawurldecode($matches[1])));
    }

    protected static function isGoogleMapsHost(string $host): bool
    {
        $host = strtolower($host);

        return $host === 'maps.google.com'
            || $host === 'www.google.com'
            || $host === 'google.com'
            || str_starts_with($host, 'www.google.')
            || str_starts_with($host, 'maps.google.');
    }
}
