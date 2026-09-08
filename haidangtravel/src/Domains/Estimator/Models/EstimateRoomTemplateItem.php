<?php

namespace Src\Domains\Estimator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateRoomTemplateItem extends Model
{
    protected $table = 'estimate_room_template_items';

    protected $fillable = [
        'room_template_id',
        'catalog_item_id',
        'default_quantity_formula',
        'sort_order',
    ];

    public function roomTemplate(): BelongsTo
    {
        return $this->belongsTo(EstimateRoomTemplate::class, 'room_template_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(EstimateCatalogItem::class, 'catalog_item_id');
    }
}
