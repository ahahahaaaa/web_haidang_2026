<?php

namespace App\Services\Travel;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Throwable;

class TourAgencyPushSyncService
{
    protected const PAYLOAD_SCHEMA_VERSION = 'cms_legacy_agency_v3';

    public function __construct(protected HaidangAgencyApiClient $client) {}

    /**
     * @param  array<int, array<string, mixed>>  $deletedDepartures
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function push(int|Tour $tour, array $deletedDepartures = [], array $options = []): array
    {
        $syncLogId = (string) Str::uuid();
        $requestedTourId = $tour instanceof Tour ? (int) $tour->getKey() : $tour;

        if (! (bool) config('tour_sync.push_enabled', true)) {
            $this->logSkipped($syncLogId, 'disabled', $tour);

            return ['skipped' => true, 'reason' => 'disabled'];
        }

        if (! $this->client->configured()) {
            $this->logSkipped($syncLogId, 'not_configured', $tour);

            return ['skipped' => true, 'reason' => 'not_configured'];
        }

        $tour = $tour instanceof Tour ? $tour : Tour::query()->find($tour);

        if (! $tour) {
            $this->logSkipped($syncLogId, 'missing_tour', $requestedTourId);

            return ['skipped' => true, 'reason' => 'missing_tour'];
        }

        $sourceState = $this->sourceState($tour);

        if (! $sourceState) {
            $this->logSkipped($syncLogId, 'missing_source_mapping', $tour);

            return ['skipped' => true, 'reason' => 'missing_source_mapping'];
        }

        $tour->load(['departures' => fn ($query) => $query
            ->orderBy('departure_date')
            ->orderBy('sort_order')
            ->orderBy('id'),
            'manager.roles',
        ]);

        $payload = $this->payload($tour, $sourceState, $deletedDepartures, $syncLogId, $options);
        Log::info('tour_sync.master_data_dashboard.payload_prepared', $this->syncLogContext($syncLogId, $tour, $sourceState, $payload) + [
            'payload' => $payload,
        ]);

        Log::info('tour_sync.master_data_dashboard.request_sending', $this->syncLogContext($syncLogId, $tour, $sourceState, $payload));

        try {
            $response = $this->client->pushTourUpdate($payload);
        } catch (Throwable $exception) {
            Log::error('tour_sync.master_data_dashboard.response_failed', $this->syncLogContext($syncLogId, $tour, $sourceState, $payload) + [
                'exception_class' => $exception::class,
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
            ] + ($exception instanceof HaidangAgencyApiException ? $exception->context() : []));

            throw $exception;
        }

        $responseStatus = (string) (data_get($response, 'status') ?? data_get($response, 'data.status') ?? 'unknown');
        $mappingRows = $this->mappingRows($response);

        Log::info('tour_sync.master_data_dashboard.response_received', $this->syncLogContext($syncLogId, $tour, $sourceState, $payload) + [
            'response_status' => $responseStatus,
            'returned_mappings_count' => $mappingRows->count(),
            'response' => $response,
        ]);

        $this->syncResponseMappings($tour, $sourceState, $response, $payload);
        $this->markDeletedDeparturesSynced($deletedDepartures);

        return [
            'skipped' => false,
            'tour_id' => $tour->getKey(),
            'source_tour_id' => $sourceState->source_tour_id,
            'departures' => count($payload['departures']),
            'deleted_departures' => count($payload['deleted_startdates']),
            'sync_mode' => (string) ($payload['sync_mode'] ?? 'full'),
            'response_status' => $responseStatus,
            'response_tour_id' => $this->integerOrNull(data_get($response, 'data.tour_id') ?? data_get($response, 'tour_id')),
            'response_tour_code' => $this->stringOrNull(data_get($response, 'data.tour_code') ?? data_get($response, 'tour_code')),
            'returned_mappings_count' => $mappingRows->count(),
            'returned_startdate_ids' => $mappingRows
                ->pluck('startdate_id')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    protected function logSkipped(string $syncLogId, string $reason, int|Tour|null $tour): void
    {
        Log::notice('tour_sync.master_data_dashboard.skipped', [
            'sync_log_id' => $syncLogId,
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'reason' => $reason,
            'cms_tour_id' => $tour instanceof Tour ? $tour->getKey() : $tour,
            'push_path' => (string) config('tour_sync.push_path'),
            'queue_name' => (string) config('tour_sync.push_queue', 'default'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function syncLogContext(string $syncLogId, Tour $tour, TourDepartureSyncState $sourceState, array $payload): array
    {
        $departures = collect($payload['departures'] ?? []);
        $deletedStartdates = collect($payload['deleted_startdates'] ?? []);

        return [
            'sync_log_id' => $syncLogId,
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => (string) ($payload['event'] ?? 'tour_saved'),
            'payload_schema_version' => (string) ($payload['payload_schema_version'] ?? self::PAYLOAD_SCHEMA_VERSION),
            'sync_mode' => (string) ($payload['sync_mode'] ?? 'full'),
            'push_path' => (string) config('tour_sync.push_path'),
            'queue_name' => (string) config('tour_sync.push_queue', 'default'),
            'cms_tour_id' => (int) $tour->getKey(),
            'source_tour_id' => $sourceState->source_tour_id,
            'tour_code' => $sourceState->tour_code,
            'tour_title' => $tour->title,
            'tour_slug' => $tour->slug,
            'transport' => data_get($payload, 'tour.transport'),
            'standard_label' => data_get($payload, 'tour.standard_label'),
            'duration_days' => data_get($payload, 'tour.duration_days'),
            'duration_nights' => data_get($payload, 'tour.duration_nights'),
            'selling_price' => data_get($payload, 'tour.price'),
            'departures_count' => $departures->count(),
            'deleted_startdates_count' => $deletedStartdates->count(),
            'departure_startdate_ids' => $departures
                ->pluck('startdate_id')
                ->filter(fn ($value): bool => $value !== null && $value !== '')
                ->values()
                ->all(),
            'departure_transport_labels' => $departures
                ->pluck('transport_label')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'departure_prices' => $departures
                ->pluck('price')
                ->filter(fn ($value): bool => $value !== null && $value !== '')
                ->unique()
                ->values()
                ->all(),
            'deleted_startdate_ids' => $deletedStartdates
                ->pluck('startdate_id')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    protected function sourceState(Tour $tour): ?TourDepartureSyncState
    {
        return TourDepartureSyncState::query()
            ->where('tour_id', $tour->getKey())
            ->whereNotNull('source_tour_id')
            ->latest('last_synced_at')
            ->latest('updated_at')
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $deletedDepartures
     * @return array<string, mixed>
     */
    protected function payload(Tour $tour, TourDepartureSyncState $sourceState, array $deletedDepartures, string $syncLogId, array $options = []): array
    {
        $mappedDeparturesOnly = (bool) ($options['mapped_departures_only'] ?? false);

        return [
            'source' => 'haidangtravel_cms',
            'event' => 'tour_saved',
            'client_sync_log_id' => $syncLogId,
            'payload_schema_version' => self::PAYLOAD_SCHEMA_VERSION,
            'sync_mode' => $mappedDeparturesOnly ? 'existing_only' : 'full',
            'synced_at' => now()->toIso8601String(),
            'tour' => $this->tourPayload($tour, $sourceState),
            'departures' => $this->departurePayloads($tour, $sourceState, $mappedDeparturesOnly),
            'deleted_startdates' => collect($deletedDepartures)
                ->map(fn (array $state): array => [
                    'cms_tour_id' => $this->integerOrNull($state['cms_tour_id'] ?? null),
                    'cms_departure_id' => $this->integerOrNull($state['cms_departure_id'] ?? $state['tour_departure_id'] ?? null),
                    'tour_id' => $this->integerOrNull($state['tour_id'] ?? null) ?: $sourceState->source_tour_id,
                    'tour_code' => $this->stringOrNull($state['tour_code'] ?? null) ?: $sourceState->tour_code,
                    'startdate_id' => $this->integerOrNull($state['startdate_id'] ?? $state['source_startdate_id'] ?? null),
                    'deleted_at' => now()->toIso8601String(),
                ])
                ->filter(fn (array $state): bool => $state['startdate_id'] !== null)
                ->values()
                ->all(),
        ];
    }

    protected function tourPayload(Tour $tour, TourDepartureSyncState $sourceState): array
    {
        $scope = $this->tourScopeValue($tour->scope);
        $excerpt = $this->stringOrNull($tour->excerpt);
        $isOutbound = $this->agencyOutboundValue($scope);
        $durationDays = $this->integerOrNull($tour->duration_days) ?: 1;
        $durationNights = $this->integerOrNull($tour->duration_nights) ?? max(0, $durationDays - 1);
        $transport = $this->stringOrNull($tour->transport) ?: $this->firstDepartureString($tour, 'transport_label') ?: 'Chưa cập nhật';
        $sellingPrice = $this->sellingPrice($tour->sale_price, $tour->base_price);

        return $this->withoutNullValues([
            'cms_tour_id' => (int) $tour->getKey(),
            'tour_id' => $sourceState->source_tour_id,
            'tour_code' => $this->stringOrNull($sourceState->tour_code),
            'tour_name' => $this->stringOrNull($tour->title),
            'title' => $this->stringOrNull($tour->title),
            'slug' => $this->stringOrNull($tour->slug),
            'status' => $this->agencyTourStatusValue($tour->status),
            'manager_email' => $this->stringOrNull($tour->manager?->email),
            'manager' => $this->managerPayload($tour->manager),
            'scope' => $scope,
            'isOutbound' => $isOutbound,
            'tour_type' => $isOutbound,
            'excerpt' => $excerpt,
            'seodescription' => $excerpt,
            'departure_location' => $this->stringOrNull($tour->departure_location),
            'transport' => $transport,
            'duration_days' => $durationDays,
            'duration_nights' => $durationNights,
            'standard_label' => $this->stringOrNull($tour->standard_label),
            'price' => $sellingPrice,
            'base_price' => $sellingPrice,
            'sale_price' => $sellingPrice,
            'sort_order' => (int) $tour->sort_order,
            'published_at' => optional($tour->published_at)?->toIso8601String(),
            'updated_at' => optional($tour->updated_at)?->toIso8601String(),
        ]);
    }

    protected function managerPayload(?User $manager): ?array
    {
        if (! $manager) {
            return null;
        }

        return $this->withoutNullValues([
            'cms_user_id' => (int) $manager->getKey(),
            'name' => $this->stringOrNull($manager->name),
            'email' => $this->stringOrNull($manager->email),
            'phone' => $this->stringOrNull($manager->phone),
            'is_active' => (bool) $manager->is_active,
            'roles' => method_exists($manager, 'getRoleNames')
                ? $manager->getRoleNames()
                    ->map(fn (mixed $role): ?string => $this->stringOrNull($role))
                    ->filter()
                    ->values()
                    ->all()
                : [],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function departurePayloads(Tour $tour, TourDepartureSyncState $sourceState, bool $mappedDeparturesOnly = false): array
    {
        return $tour->departures
            ->map(fn (TourDeparture $departure): ?array => $this->departurePayload($departure, $sourceState, $tour, $mappedDeparturesOnly))
            ->filter()
            ->values()
            ->all();
    }

    protected function departurePayload(TourDeparture $departure, TourDepartureSyncState $sourceState, Tour $tour, bool $mappedDeparturesOnly = false): ?array
    {
        $state = TourDepartureSyncState::query()
            ->where('tour_id', $departure->tour_id)
            ->where('tour_departure_id', $departure->getKey())
            ->where('source_tour_id', $sourceState->source_tour_id)
            ->first();

        if ($mappedDeparturesOnly && ! $state?->source_startdate_id) {
            return null;
        }

        $availableSlots = $this->integerOrNull($departure->available_slots);
        $departureStatus = $this->agencyDepartureStatusValue($departure->status);
        $departureDate = optional($departure->departure_date)?->toDateString();
        $returnDate = optional($departure->return_date)?->toDateString()
            ?: $this->fallbackReturnDate($departureDate, $tour->duration_days);
        $traffic = $this->stringOrNull($departure->transport_label) ?: $this->stringOrNull($tour->transport);
        $sellingPrice = $this->sellingPrice($departure->sale_price, $departure->base_price)
            ?? $this->sellingPrice($tour->sale_price, $tour->base_price);

        if (! $departureDate || ! $returnDate || ! $traffic || $sellingPrice === null || ! $departureStatus) {
            Log::warning('tour_sync.master_data_dashboard.departure_skipped', [
                'source' => 'haidangtravel_cms',
                'target' => 'api_master_data_dashboard',
                'event' => 'departure_skipped',
                'cms_tour_id' => $tour->getKey(),
                'cms_departure_id' => $departure->getKey(),
                'source_tour_id' => $sourceState->source_tour_id,
                'tour_code' => $sourceState->tour_code,
                'missing_departure_date' => ! $departureDate,
                'missing_return_date' => ! $returnDate,
                'missing_traffic' => ! $traffic,
                'missing_selling_price' => $sellingPrice === null,
                'missing_status' => ! $departureStatus,
            ]);

            return null;
        }

        return $this->withoutNullValues([
            'cms_departure_id' => (int) $departure->getKey(),
            'tour_id' => $sourceState->source_tour_id,
            'tour_code' => $this->stringOrNull($sourceState->tour_code),
            'startdate_id' => $state?->source_startdate_id,
            'startdate' => $departureDate,
            'enddate' => $returnDate,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'departure_location' => $this->stringOrNull($departure->departure_location),
            'traffic' => $traffic,
            'transport_label' => $traffic,
            'startdate_label' => $this->stringOrNull($departure->standard_label),
            'standard_label' => $this->stringOrNull($departure->standard_label),
            'adult_price' => $sellingPrice,
            'price' => $sellingPrice,
            'base_price' => $sellingPrice,
            'sale_price' => $sellingPrice,
            'seat' => $availableSlots,
            'total_seat' => $availableSlots,
            'save_agency' => $availableSlots,
            'available_seat' => $availableSlots,
            'available_slots' => $availableSlots,
            'notice_agency' => $this->stringOrNull($departure->pricing_note),
            'pricing_note' => $this->stringOrNull($departure->pricing_note),
            'status' => $departureStatus,
            'status_label' => $departureStatus,
            'is_agency' => $this->agencyDepartureIsOpen($departureStatus) ? 1 : 0,
            'is_featured' => (bool) $departure->is_featured,
            'sort_order' => (int) $departure->sort_order,
            'updated_at' => optional($departure->updated_at)?->toIso8601String(),
        ], ['startdate_id']);
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $payload
     */
    protected function syncResponseMappings(Tour $tour, TourDepartureSyncState $sourceState, array $response, array $payload): void
    {
        $sourceTourId = $this->integerOrNull(data_get($response, 'data.tour_id'))
            ?: $this->integerOrNull(data_get($response, 'tour_id'))
            ?: $sourceState->source_tour_id;
        $tourCode = $this->stringOrNull(data_get($response, 'data.tour_code'))
            ?: $this->stringOrNull(data_get($response, 'tour_code'))
            ?: $sourceState->tour_code;
        $payloadDepartures = collect($payload['departures'] ?? [])->keyBy('cms_departure_id');

        foreach ($this->mappingRows($response) as $row) {
            $cmsDepartureId = $this->integerOrNull($row['cms_departure_id'] ?? $row['departure_id'] ?? $row['local_departure_id'] ?? null);
            $sourceStartdateId = $this->integerOrNull($row['startdate_id'] ?? $row['source_startdate_id'] ?? $row['agency_startdate_id'] ?? null);

            if (! $cmsDepartureId || ! $sourceStartdateId) {
                continue;
            }

            TourDepartureSyncState::query()->updateOrCreate(
                [
                    'tour_id' => $tour->getKey(),
                    'source_startdate_id' => $sourceStartdateId,
                ],
                [
                    'tour_departure_id' => $cmsDepartureId,
                    'source_tour_id' => $sourceTourId,
                    'tour_code' => $tourCode,
                    'source_checksum' => $this->checksum((array) $payloadDepartures->get($cmsDepartureId, [])),
                    'last_synced_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function mappingRows(array $response): Collection
    {
        foreach ([
            'data.startdates',
            'data.departures',
            'data.startdate_mappings',
            'startdates',
            'departures',
            'startdate_mappings',
        ] as $path) {
            $rows = data_get($response, $path);

            if (is_array($rows) && array_is_list($rows)) {
                return collect($rows)->filter(fn ($row): bool => is_array($row))->values();
            }
        }

        return collect();
    }

    /**
     * @param  array<int, array<string, mixed>>  $deletedDepartures
     */
    protected function markDeletedDeparturesSynced(array $deletedDepartures): void
    {
        foreach ($deletedDepartures as $state) {
            $sourceStartdateId = $this->integerOrNull($state['startdate_id'] ?? $state['source_startdate_id'] ?? null);

            if (! $sourceStartdateId) {
                continue;
            }

            $query = TourDepartureSyncState::query()
                ->where('source_startdate_id', $sourceStartdateId);
            $cmsTourId = $this->integerOrNull($state['cms_tour_id'] ?? null);

            if ($cmsTourId) {
                $query->where('tour_id', $cmsTourId);
            }

            $query->update(['last_synced_at' => now()]);
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

    protected function firstDepartureString(Tour $tour, string $field): ?string
    {
        return $tour->departures
            ->map(fn (TourDeparture $departure): ?string => $this->stringOrNull($departure->{$field} ?? null))
            ->filter()
            ->first();
    }

    protected function fallbackReturnDate(?string $departureDate, mixed $durationDays): ?string
    {
        if (! $departureDate) {
            return null;
        }

        try {
            $days = max(1, $this->integerOrNull($durationDays) ?: 1);

            return Carbon::parse($departureDate)->addDays($days - 1)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function sellingPrice(mixed $salePrice, mixed $basePrice): ?int
    {
        return $this->integerOrNull($salePrice) ?? $this->integerOrNull($basePrice);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keepNullKeys
     * @return array<string, mixed>
     */
    protected function withoutNullValues(array $payload, array $keepNullKeys = []): array
    {
        $filtered = [];

        foreach ($payload as $key => $value) {
            if ($value === null && ! in_array((string) $key, $keepNullKeys, true)) {
                continue;
            }

            $filtered[$key] = is_array($value)
                ? $this->withoutNullValues($value)
                : $value;
        }

        return $filtered;
    }

    protected function tourScopeValue(mixed $scope): ?string
    {
        if ($scope instanceof TourScope) {
            return $scope->value;
        }

        return $this->stringOrNull($scope);
    }

    protected function agencyOutboundValue(?string $scope): int
    {
        $scope = Str::of((string) $scope)->lower()->trim()->replace('_', '-')->value();

        return in_array($scope, ['international', 'outbound', 'nuoc-ngoai'], true) ? 1 : 0;
    }

    protected function agencyTourStatusValue(mixed $status): ?string
    {
        $status = Str::of((string) $status)->lower()->trim()->value();

        return match ($status) {
            'published', 'active', 'open', 'scheduled' => 'published',
            'archived', 'inactive', 'closed', 'cancelled', 'canceled', 'hidden' => 'inactive',
            'draft' => 'draft',
            default => $this->stringOrNull($status),
        };
    }

    protected function agencyDepartureStatusValue(mixed $status): ?string
    {
        $status = Str::of((string) $status)->lower()->trim()->value();

        return match ($status) {
            'sold_out', 'finished', 'closed', 'inactive', 'hidden' => 'closed',
            'cancelled', 'canceled' => 'cancelled',
            default => $this->stringOrNull($status),
        };
    }

    protected function agencyDepartureIsOpen(?string $status): bool
    {
        return ! in_array($status, ['closed', 'inactive', 'hidden', 'cancelled', 'canceled'], true);
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
