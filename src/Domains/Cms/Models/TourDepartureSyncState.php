<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDepartureSyncState extends Model
{
    protected $table = 'tour_departure_sync_states';

    protected $fillable = [
        'tour_id',
        'tour_departure_id',
        'source_tour_id',
        'tour_code',
        'source_startdate_id',
        'source_checksum',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'source_startdate_id' => 'integer',
            'source_tour_id' => 'integer',
        ];
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
