<?php

namespace Src\Domains\Cms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourAgencyPushSyncRun extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'tour_agency_push_sync_runs';

    protected $fillable = [
        'tour_id',
        'created_by_user_id',
        'tour_title',
        'source_tour_id',
        'tour_code',
        'trigger',
        'status',
        'queue_name',
        'attempts',
        'deleted_departures',
        'summary',
        'last_error',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'deleted_departures' => 'array',
            'finished_at' => 'datetime',
            'queued_at' => 'datetime',
            'source_tour_id' => 'integer',
            'started_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
