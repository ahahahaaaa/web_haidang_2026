<?php

namespace App\Services\Frontsite;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FrontsiteCache
{
    protected const VERSION_KEY = 'frontsite:versions';

    protected const VERSION_LOCK_KEY = 'frontsite:versions:lock';

    protected const LAST_CLEAR_KEY = 'frontsite:last_clear';

    protected ?array $cachedVersions = null;

    public function enabled(): bool
    {
        return (bool) config('frontsite_cache.enabled', true);
    }

    public function forgetGroups(array|string $groups): array
    {
        $groups = $this->normalizeGroups($groups);
        $clearedAt = now()->toIso8601String();

        if ($groups === []) {
            return $this->forgetResult([], [], [], $clearedAt);
        }

        $affectedGroups = $this->groupsWithGlobal($groups);
        $beforeVersions = $this->versionsForGroups($this->versions(), $affectedGroups);
        $afterVersions = $beforeVersions;

        $callback = function () use ($groups, $affectedGroups, $clearedAt, &$afterVersions): void {
            $versions = Cache::get(self::VERSION_KEY, []);
            $versions = is_array($versions) ? $versions : [];

            foreach ($groups as $group) {
                $versions[$group] = ($versions[$group] ?? 1) + 1;
            }

            Cache::forever(self::VERSION_KEY, $versions);
            Cache::forever(self::LAST_CLEAR_KEY, [
                'groups' => $groups,
                'affected_groups' => $affectedGroups,
                'cleared_at' => $clearedAt,
                'versions' => $this->versionsForGroups($versions, $affectedGroups),
            ]);

            $this->cachedVersions = $versions;
            $afterVersions = $this->versionsForGroups($versions, $affectedGroups);
        };

        rescue(
            fn () => Cache::lock(self::VERSION_LOCK_KEY, 10)->block(3, $callback),
            fn () => $callback(),
            report: false,
        );

        return $this->forgetResult($groups, $beforeVersions, $afterVersions, $clearedAt);
    }

    public function forgetAll(): array
    {
        return $this->forgetGroups('all');
    }

    public function key(string $key, array|string $groups = []): string
    {
        $groups = $this->groupsWithGlobal($groups);
        $versions = $this->versionsFor($groups);
        $versionHash = substr(sha1(json_encode($versions, JSON_THROW_ON_ERROR)), 0, 16);
        $safeKey = Str::of($key)->replaceMatches('/[^A-Za-z0-9:_\\-.]+/', ':')->trim(':')->value();

        return 'frontsite:v2:'.$versionHash.':'.$safeKey;
    }

    public function remember(string $key, array|string $groups, Closure $callback, ?int $ttl = null): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        return Cache::memo()->remember(
            $this->key($key, $groups),
            $ttl ?? $this->ttl('default'),
            $callback,
        );
    }

    public function rememberFlexible(string $key, array|string $groups, array $ttl, Closure $callback): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        if (! (bool) config('frontsite_cache.stale.enabled', true)) {
            return $this->remember($key, $groups, $callback, (int) ($ttl[0] ?? $this->ttl('default')));
        }

        return Cache::flexible(
            $this->key($key, $groups),
            [
                max(1, (int) ($ttl[0] ?? $this->ttl('default'))),
                max(1, (int) ($ttl[1] ?? $this->ttl('default'))),
            ],
            $callback,
        );
    }

    public function ttl(string $name): int
    {
        return max(1, (int) config('frontsite_cache.ttl.'.$name, config('frontsite_cache.ttl.default', 1800)));
    }

    public function status(): array
    {
        $versions = $this->versions();
        $defaultStore = (string) config('cache.default');
        $lastClear = Cache::get(self::LAST_CLEAR_KEY);

        return [
            'enabled' => $this->enabled(),
            'response_enabled' => (bool) config('frontsite_cache.middleware.enabled', true),
            'stale_enabled' => (bool) config('frontsite_cache.stale.enabled', true),
            'store' => $defaultStore,
            'driver' => (string) config('cache.stores.'.$defaultStore.'.driver', $defaultStore),
            'header' => (string) config('frontsite_cache.middleware.header', 'X-Frontsite-Cache'),
            'ttl' => [
                'default' => $this->ttl('default'),
                'chrome' => $this->ttl('chrome'),
                'query' => $this->ttl('query'),
                'sitemap' => $this->ttl('sitemap'),
                'response' => $this->ttl('response'),
            ],
            'versions' => collect($versions)->sortKeys()->all(),
            'global_version' => (int) ($versions['all'] ?? 1),
            'tracked_group_count' => count($versions),
            'last_clear' => is_array($lastClear) ? $lastClear : null,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    protected function groupsWithGlobal(array|string $groups): array
    {
        return $this->normalizeGroups(['all', ...$this->normalizeGroups($groups)]);
    }

    protected function normalizeGroups(array|string $groups): array
    {
        return collect((array) $groups)
            ->map(fn (mixed $group): string => trim((string) $group))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function versions(): array
    {
        if ($this->cachedVersions !== null) {
            return $this->cachedVersions;
        }

        $versions = Cache::rememberForever(self::VERSION_KEY, fn (): array => []);

        return $this->cachedVersions = is_array($versions) ? $versions : [];
    }

    protected function versionsFor(array $groups): array
    {
        return $this->versionsForGroups($this->versions(), $groups);
    }

    protected function versionsForGroups(array $versions, array $groups): array
    {
        return collect($groups)
            ->mapWithKeys(fn (string $group): array => [$group => (int) ($versions[$group] ?? 1)])
            ->all();
    }

    protected function forgetResult(array $groups, array $beforeVersions, array $afterVersions, string $clearedAt): array
    {
        return [
            'groups' => $groups,
            'affected_groups' => array_keys($afterVersions),
            'before' => $beforeVersions,
            'after' => $afterVersions,
            'cleared_at' => $clearedAt,
        ];
    }
}
