<?php

namespace App\Services\Travel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TourSyncSourceTourCatalog
{
    protected const MAX_PAGES = 50;

    public function __construct(protected HaidangAgencyApiClient $client) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(bool $refresh = false): array
    {
        if ($refresh) {
            $this->forget();
        }

        return Cache::remember(
            $this->cacheKey(),
            now()->endOfDay(),
            fn (): array => $this->fetchAll(),
        );
    }

    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $sourceTourId, bool $refresh = false): ?array
    {
        $sourceTourId = (string) $sourceTourId;

        return collect($this->all($refresh))
            ->first(fn (array $row): bool => (string) ($row['tour_id'] ?? '') === $sourceTourId);
    }

    public function cacheDate(): string
    {
        return now()->toDateString();
    }

    protected function cacheKey(): string
    {
        return 'tour-sync-source-tours:'.$this->cacheDate();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAll(): array
    {
        $rows = collect();
        $page = 1;

        do {
            $payload = $this->client->tours([
                'include_inactive' => 1,
                'page' => $page,
                'per_page' => (int) config('tour_sync.per_page', 100),
            ]);
            $rows = $rows->merge($this->rowsFromPayload($payload));
            $meta = data_get($payload, 'meta', []);
            $lastPage = (int) data_get($meta, 'last_page', $page);
            $nextPage = data_get($meta, 'next_page');

            $page = is_numeric($nextPage) ? (int) $nextPage : $page + 1;
        } while ($page <= max(1, $lastPage) && $page <= self::MAX_PAGES);

        return $rows
            ->map(fn (array $row): array => $this->normalizeRow($row))
            ->filter(fn (array $row): bool => $row['tour_id'] !== null)
            ->unique(fn (array $row): string => (string) $row['tour_id'])
            ->sortBy(fn (array $row): string => Str::ascii((string) $row['title']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $title = $this->stringOrNull($row['tour_name'] ?? $row['title'] ?? $row['name'] ?? null);
        $transportCandidates = [
            'transport' => $this->stringOrNull($row['transport'] ?? null),
            'traffic' => $this->stringOrNull($row['traffic'] ?? null),
            'transport_label' => $this->stringOrNull($row['transport_label'] ?? null),
        ];
        $transportSourceField = collect($transportCandidates)
            ->filter(fn (?string $value): bool => $value !== null)
            ->keys()
            ->first();

        return [
            'tour_id' => $this->integerOrNull($row['tour_id'] ?? $row['id'] ?? null),
            'tour_name' => $title,
            'title' => $title,
            'tour_code' => $this->stringOrNull($row['tour_code'] ?? $row['code'] ?? null),
            'slug' => $this->stringOrNull($row['slug'] ?? null),
            'status' => $this->stringOrNull($row['status'] ?? null),
            'status_label' => $this->stringOrNull($row['status_label'] ?? null),
            'transport' => $transportSourceField ? $transportCandidates[$transportSourceField] : null,
            'transport_source_field' => $transportSourceField,
            'transport_candidates' => collect($transportCandidates)
                ->filter(fn (?string $value): bool => $value !== null)
                ->all(),
            'standard_label' => $this->stringOrNull($row['standard_label'] ?? $row['startdate_label'] ?? null),
            'duration_days' => $this->integerOrNull($row['duration_days'] ?? $row['days'] ?? null),
            'duration_nights' => $this->integerOrNull($row['duration_nights'] ?? $row['nights'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function rowsFromPayload(array $payload): Collection
    {
        $data = data_get($payload, 'data');

        if (is_array($data) && array_is_list($data)) {
            return collect($data)->filter(fn ($row) => is_array($row))->values();
        }

        foreach (['data.data', 'data.items', 'items', 'rows'] as $path) {
            $rows = data_get($payload, $path);

            if (is_array($rows) && array_is_list($rows)) {
                return collect($rows)->filter(fn ($row) => is_array($row))->values();
            }
        }

        return collect();
    }

    protected function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        return $normalized !== '' ? (int) $normalized : null;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? Str::limit($value, 255, '') : null;
    }
}
