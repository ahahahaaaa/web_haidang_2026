<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SitewideHtmlSnippets
{
    protected const ZALO_SDK_URL = 'https://sp.zalo.me/plugins/sdk.js';

    protected const ZALO_WIDGET_STYLE = 'position: fixed; left: auto; right: 16px; bottom: 16px; z-index: 60; width: 60px; height: 60px; max-width: 60px; max-height: 60px; contain: layout size;';

    protected const ZALO_IDLE_DELAY_MS = 12000;

    /**
     * @var array<string, bool>
     */
    protected static array $conversionExistsCache = [];

    public static function htmlSnippetContent(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return FrontsiteUrls::normalizeInternalHtml($value);
    }

    public static function afterHeaderHtml(mixed $value): ?string
    {
        $html = self::htmlSnippetContent($value);

        return $html !== null ? self::optimizePerformance($html) : null;
    }

    public static function endBodyHtml(mixed $value): ?string
    {
        $html = self::htmlSnippetContent($value);

        return $html !== null ? self::deferZaloSdk(self::optimizePerformance($html)) : null;
    }

    public static function deferZaloSdk(string $html): string
    {
        $pattern = '/<script\b(?=[^>]*\bsrc\s*=\s*([\'\"])https:\/\/sp\.zalo\.me\/plugins\/sdk\.js\1)[^>]*>\s*<\/script>/i';

        if (! preg_match($pattern, $html)) {
            return $html;
        }

        return (string) preg_replace($pattern, self::deferredZaloSdkLoader(), $html, 1);
    }

    public static function deferredZaloSdkLoader(): string
    {
        $delay = self::ZALO_IDLE_DELAY_MS;

        return <<<HTML
<script>
(() => {
  let loaded = false;
  let fallbackTimer = null;
  const loadZalo = () => {
    if (loaded) return;
    loaded = true;
    if (fallbackTimer) window.clearTimeout(fallbackTimer);
    const script = document.createElement('script');
    script.src = 'https://sp.zalo.me/plugins/sdk.js';
    script.async = true;
    document.body.appendChild(script);
  };

  ['pointerdown', 'touchstart', 'keydown', 'scroll'].forEach((eventName) => {
    window.addEventListener(eventName, loadZalo, { once: true, passive: true });
  });

  window.addEventListener('load', () => {
    fallbackTimer = window.setTimeout(loadZalo, {$delay});
  }, { once: true });
})();
</script>
HTML;
    }

    protected static function optimizePerformance(string $html): string
    {
        return self::withLazyImageDefaults(self::withStableZaloWidget($html));
    }

    protected static function withStableZaloWidget(string $html): string
    {
        return (string) preg_replace_callback(
            '/<div\b(?=[^>]*\bzalo-chat-widget\b)[^>]*>/i',
            fn (array $matches): string => self::mergeStyleAttribute($matches[0], self::ZALO_WIDGET_STYLE),
            $html,
        );
    }

    protected static function withLazyImageDefaults(string $html): string
    {
        return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $matches): string {
            $tag = $matches[0];

            if (! self::hasAttribute($tag, 'loading') && ! self::hasAttributeValue($tag, 'fetchpriority', 'high')) {
                $tag = self::appendAttributes($tag, ['loading' => 'lazy']);
            }

            if (! self::hasAttribute($tag, 'decoding')) {
                $tag = self::appendAttributes($tag, ['decoding' => 'async']);
            }

            if (! self::hasAttribute($tag, 'fetchpriority') && ! self::hasAttributeValue($tag, 'loading', 'eager')) {
                $tag = self::appendAttributes($tag, ['fetchpriority' => 'low']);
            }

            $src = self::attributeValue($tag, 'src');
            $srcset = $src !== null ? self::storageImageSrcset($src) : null;

            if ($srcset !== null && ! self::hasAttribute($tag, 'srcset')) {
                $tag = self::appendAttributes($tag, ['srcset' => $srcset]);

                if (! self::hasAttribute($tag, 'sizes')) {
                    $tag = self::appendAttributes($tag, ['sizes' => '(max-width: 767px) calc(100vw - 2rem), 640px']);
                }
            }

            return $tag;
        }, $html);
    }

    protected static function mergeStyleAttribute(string $tag, string $style): string
    {
        if (self::hasAttribute($tag, 'style')) {
            return (string) preg_replace_callback(
                '/\sstyle\s*=\s*([\'\"])(.*?)\1/i',
                fn (array $matches): string => ' style='.$matches[1].rtrim($matches[2], '; ').'; '.$style.$matches[1],
                $tag,
                1,
            );
        }

        return self::appendAttributes($tag, ['style' => $style]);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected static function appendAttributes(string $tag, array $attributes): string
    {
        $attributeString = '';

        foreach ($attributes as $name => $value) {
            $attributeString .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
        }

        $closing = Str::endsWith(trim($tag), '/>') ? ' />' : '>';

        return (string) preg_replace('/\s*\/?>$/', $attributeString.$closing, $tag, 1);
    }

    protected static function hasAttribute(string $tag, string $attribute): bool
    {
        return (bool) preg_match('/\s'.preg_quote($attribute, '/').'\b(?:\s*=|\s|\/?>)/i', $tag);
    }

    protected static function hasAttributeValue(string $tag, string $attribute, string $expected): bool
    {
        $value = self::attributeValue($tag, $attribute);

        return $value !== null && Str::lower($value) === Str::lower($expected);
    }

    protected static function attributeValue(string $tag, string $attribute): ?string
    {
        if (! preg_match('/\s'.preg_quote($attribute, '/').'\s*=\s*([\'\"])(.*?)\1/i', $tag, $matches)) {
            return null;
        }

        return html_entity_decode((string) $matches[2], ENT_QUOTES, 'UTF-8');
    }

    protected static function storageImageSrcset(string $src): ?string
    {
        $relativePath = self::storageRelativePath($src);

        if ($relativePath === null || str_contains($relativePath, '/conversions/')) {
            return null;
        }

        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $directory = trim(str_replace('\\', '/', dirname($relativePath)), './');

        if ($extension === '' || $filename === '' || $directory === '') {
            return null;
        }

        $items = [];

        foreach ([FrontsiteMedia::SIZE_SMALL => 500, FrontsiteMedia::SIZE_MEDIUM => 1000] as $size => $width) {
            $candidate = $directory.'/conversions/'.$filename.'-'.$size.'.'.$extension;

            if (self::publicStorageExists($candidate)) {
                $items[] = FrontsiteUrls::canonicalUrl('/storage/'.$candidate).' '.$width.'w';
            }
        }

        $items[] = (FrontsiteUrls::cleanInternalUrl($src, true) ?: $src).' 1200w';
        $items = array_values(array_unique($items));

        return count($items) > 1 ? implode(', ', $items) : null;
    }

    protected static function storageRelativePath(string $url): ?string
    {
        $parts = parse_url($url);
        $path = trim((string) ($parts['path'] ?? $url));
        $normalizedPath = '/'.ltrim($path, '/');

        if (! Str::startsWith($normalizedPath, '/storage/')) {
            return null;
        }

        $relativePath = rawurldecode(ltrim(Str::after($normalizedPath, '/storage/'), '/'));

        return $relativePath !== '' ? $relativePath : null;
    }

    protected static function publicStorageExists(string $relativePath): bool
    {
        if (! array_key_exists($relativePath, self::$conversionExistsCache)) {
            try {
                self::$conversionExistsCache[$relativePath] = Storage::disk('public')->exists($relativePath);
            } catch (\Throwable) {
                self::$conversionExistsCache[$relativePath] = false;
            }
        }

        return self::$conversionExistsCache[$relativePath];
    }
}