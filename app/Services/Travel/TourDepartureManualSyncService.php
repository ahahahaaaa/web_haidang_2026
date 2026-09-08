<?php

namespace App\Services\Travel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourDepartureSyncState;

class TourDepartureManualSyncService
{
    public function __construct(protected HaidangAgencyApiClient $client) {}

    /**
     * @return array<string, mixed>
     */
    public function sync(Tour $tour): array
    {
        return $this->syncFromSourceTour($tour, $this->resolveSourceTour($tour));
    }

    /**
     * @param  array<string, mixed>  $sourceTour
     * @return array<string, mixed>
     */
    public function syncFromSourceTour(Tour $tour, array $sourceTour): array
    {
        $sourceTourId = $this->integerOrNull($sourceTour['tour_id'] ?? null);
        $tourCode = $this->stringOrNull($sourceTour['tour_code'] ?? null);
        $sourceTitle = $this->stringOrNull($sourceTour['tour_name'] ?? $sourceTour['title'] ?? null);
        $sourceSlug = $this->slugOrNull($sourceTour['slug'] ?? null);
        $sourceTourDetails = $this->tourDetailPayload($sourceTour);

        if ($sourceTourId === null && $tourCode === null) {
            throw new HaidangAgencyApiException('Tour nguồn chưa có ID hoặc mã tour để đồng bộ.');
        }

        $rows = $this->futureStartdateRows($this->fetchStartdateRows($tour, $sourceTourId, $tourCode));
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped_past' => 0,
            'skipped_invalid' => 0,
            'received' => $rows->count(),
            'source_tour_id' => $sourceTourId,
            'tour_code' => $tourCode,
            'tour_title_updated' => false,
            'tour_slug_updated' => false,
            'tour_slug_skipped_duplicate' => false,
            'tour_details_updated' => false,
        ];

        DB::transaction(function () use ($rows, $tour, $sourceTourId, $tourCode, $sourceTitle, $sourceSlug, $sourceTourDetails, &$summary): void {
            $tourUpdates = [];

            if ($sourceTitle !== null && $sourceTitle !== $tour->title) {
                $tourUpdates['title'] = $sourceTitle;
            }

            if ($sourceSlug !== null && $sourceSlug !== $tour->slug) {
                if ($this->slugBelongsToAnotherTour($tour, $sourceSlug)) {
                    $summary['tour_slug_skipped_duplicate'] = true;
                } else {
                    $tourUpdates['slug'] = $sourceSlug;
                }
            }

            foreach ($sourceTourDetails as $field => $value) {
                if ($this->tourFieldChanged($tour, $field, $value)) {
                    $tourUpdates[$field] = $value;
                }
            }

            if ($tourUpdates !== []) {
                $tour->forceFill($tourUpdates)->save();
                $summary['tour_title_updated'] = array_key_exists('title', $tourUpdates);
                $summary['tour_slug_updated'] = array_key_exists('slug', $tourUpdates);
                $summary['tour_details_updated'] = count(array_diff_key($tourUpdates, ['title' => true, 'slug' => true])) > 0;
            }

            foreach ($rows as $row) {
                $payload = $this->departurePayload($row);

                if ($payload === null) {
                    $summary['skipped_invalid']++;

                    continue;
                }

                $sourceStartdateId = $this->integerOrNull($row['startdate_id'] ?? null);
                $existingState = $sourceStartdateId
                    ? TourDepartureSyncState::query()
                        ->where('tour_id', $tour->getKey())
                        ->where('source_startdate_id', $sourceStartdateId)
                        ->first()
                    : null;
                $departure = $existingState?->departure ?: $this->findMatchingDeparture($tour, $payload);
                $wasRecentlyCreated = false;

                if (! $departure) {
                    $departure = $tour->departures()->create($payload);
                    $wasRecentlyCreated = true;
                    $summary['created']++;
                } else {
                    $departure->fill($payload)->save();
                    $summary['updated']++;
                }

                if ($sourceStartdateId) {
                    TourDepartureSyncState::query()->updateOrCreate(
                        [
                            'tour_id' => $tour->getKey(),
                            'source_startdate_id' => $sourceStartdateId,
                        ],
                        [
                            'tour_departure_id' => $departure->getKey(),
                            'source_tour_id' => $sourceTourId,
                            'tour_code' => $tourCode,
                            'source_checksum' => $this->checksum($row),
                            'last_synced_at' => now(),
                        ],
                    );
                }

                if ($wasRecentlyCreated) {
                    $departure->refresh();
                }
            }

            $tour->forceFill([
                'departure_schedules' => $this->futureScheduleLines($tour),
            ])->save();
        });

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $sourceTour
     * @return array<string, string|int>
     */
    protected function tourDetailPayload(array $sourceTour): array
    {
        return collect([
            'transport' => $this->stringOrNull($sourceTour['transport'] ?? $sourceTour['traffic'] ?? $sourceTour['transport_label'] ?? null),
            'duration_days' => $this->integerOrNull($sourceTour['duration_days'] ?? $sourceTour['days'] ?? null),
            'duration_nights' => $this->integerOrNull($sourceTour['duration_nights'] ?? $sourceTour['nights'] ?? null),
        ])
            ->filter(fn ($value): bool => $value !== null)
            ->all();
    }

    protected function tourFieldChanged(Tour $tour, string $field, string|int $value): bool
    {
        $current = $tour->{$field};

        if (in_array($field, ['duration_days', 'duration_nights'], true)) {
            return ($current === null ? null : (int) $current) !== $value;
        }

        return $this->stringOrNull($current) !== $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSourceTour(Tour $tour): array
    {
        $knownState = TourDepartureSyncState::query()
            ->where('tour_id', $tour->getKey())
            ->whereNotNull('source_tour_id')
            ->latest('last_synced_at')
            ->first();

        if ($knownState) {
            return [
                'tour_id' => $knownState->source_tour_id,
                'tour_code' => $knownState->tour_code,
            ];
        }

        $slugPayload = $this->client->tours([
            'q' => $tour->slug,
            'include_inactive' => 1,
            'limit' => 10,
        ]);
        $match = $this->matchTourRow($this->rowsFromPayload($slugPayload), $tour);

        if ($match !== null) {
            return $match;
        }

        $titlePayload = $this->client->tours([
            'tour_name' => $tour->title,
            'include_inactive' => 1,
            'limit' => 10,
        ]);
        $match = $this->matchTourRow($this->rowsFromPayload($titlePayload), $tour);

        if ($match !== null) {
            return $match;
        }

        throw new HaidangAgencyApiException('Không tìm thấy tour tương ứng trên cổng API Master Data DashBoard.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function fetchStartdateRows(Tour $tour, ?int $sourceTourId, ?string $tourCode): Collection
    {
        $filters = [
            'future_only' => 1,
            'per_page' => (int) config('tour_sync.per_page', 100),
        ];

        if ($sourceTourId) {
            $filters['tour_id'] = $sourceTourId;
        } elseif ($tourCode) {
            $filters['tour_code'] = $tourCode;
        } else {
            $filters['tour_name'] = $tour->title;
        }

        $rows = collect();
        $page = 1;

        do {
            $payload = $this->client->startdates($filters + ['page' => $page]);
            $rows = $rows->merge($this->rowsFromPayload($payload));
            $meta = data_get($payload, 'meta', []);
            $lastPage = (int) data_get($meta, 'last_page', $page);
            $nextPage = data_get($meta, 'next_page');

            $page = is_numeric($nextPage) ? (int) $nextPage : $page + 1;
        } while ($page <= max(1, $lastPage) && $page <= 50);

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function futureStartdateRows(Collection $rows): Collection
    {
        $today = Carbon::today();

        return $rows
            ->filter(function (array $row) use ($today): bool {
                $date = $this->parseDate($row['startdate'] ?? null);

                return $date !== null && $date->greaterThan($today);
            })
            ->values();
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

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    protected function matchTourRow(Collection $rows, Tour $tour): ?array
    {
        $localSlug = Str::slug($tour->slug);
        $localTitle = $this->normalizeName($tour->title);

        return $rows->first(function (array $row) use ($localSlug, $localTitle): bool {
            $slug = Str::slug((string) ($row['slug'] ?? ''));
            $tourCode = Str::slug((string) ($row['tour_code'] ?? ''));
            $title = $this->normalizeName((string) ($row['title'] ?? $row['tour_name'] ?? ''));

            return ($slug !== '' && $slug === $localSlug)
                || ($tourCode !== '' && $tourCode === $localSlug)
                || ($title !== '' && $title === $localTitle);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function departurePayload(array $row): ?array
    {
        $departureDate = $this->parseDate($row['startdate'] ?? null);
        $transportLabel = $this->stringOrNull($row['traffic'] ?? $row['transport_label'] ?? $row['transport'] ?? null);
        $adultPrice = $this->integerOrNull($row['adult_price'] ?? null);

        if ($departureDate === null || $transportLabel === null || $adultPrice === null) {
            return null;
        }

        return [
            'departure_date' => $departureDate->toDateString(),
            'transport_label' => $transportLabel,
            'base_price' => $adultPrice,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function findMatchingDeparture(Tour $tour, array $payload): ?TourDeparture
    {
        return $tour->departures()
            ->whereDate('departure_date', $payload['departure_date'])
            ->where('transport_label', $payload['transport_label'])
            ->first();
    }

    /**
     * @return array<int, string>
     */
    protected function futureScheduleLines(Tour $tour): array
    {
        $today = Carbon::today();
        $existingNotes = collect($tour->departure_schedules ?? [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->filter(function (string $line) use ($today): bool {
                $date = $this->parseDate($line);

                return $date === null || $date->greaterThan($today);
            });
        $futureDates = $tour->departures()
            ->published()
            ->whereDate('departure_date', '>', $today->toDateString())
            ->orderBy('departure_date')
            ->pluck('departure_date')
            ->map(fn ($date) => Carbon::parse($date)->format('d/m/Y'));

        return $existingNotes
            ->merge($futureDates)
            ->unique()
            ->values()
            ->all();
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();

            if (
                $date instanceof \DateTimeImmutable
                && ($errors === false || ((int) $errors['warning_count'] === 0 && (int) $errors['error_count'] === 0))
            ) {
                return Carbon::instance($date)->startOfDay();
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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

    protected function slugOrNull(mixed $value): ?string
    {
        $slug = Str::slug((string) $value);

        return $slug !== '' ? Str::limit($slug, 255, '') : null;
    }

    protected function slugBelongsToAnotherTour(Tour $tour, string $slug): bool
    {
        return Tour::query()
            ->where('slug', $slug)
            ->where('id', '!=', $tour->getKey())
            ->exists();
    }

    protected function normalizeName(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->value();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function checksum(array $row): string
    {
        ksort($row);

        return hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
