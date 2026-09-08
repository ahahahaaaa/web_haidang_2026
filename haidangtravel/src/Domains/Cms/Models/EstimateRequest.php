<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateRequest extends Model
{
    protected $table = 'estimate_requests';

    protected $fillable = [
        'estimate_access_key_id',
        'tier_code',
        'tier_name',
        'requires_key',
        'estimate_key_code',
        'delivery_channel',
        'mail_status',
        'customer_mail_status',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'company_name',
        'project_location',
        'project_overview',
        'page_url',
        'mailed_to',
        'mailed_at',
        'customer_mailed_to',
        'customer_mailed_at',
        'input_payload',
        'result_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'mailed_at' => 'datetime',
            'customer_mailed_at' => 'datetime',
            'requires_key' => 'boolean',
            'result_snapshot' => 'array',
        ];
    }

    public function accessKey(): BelongsTo
    {
        return $this->belongsTo(EstimateAccessKey::class, 'estimate_access_key_id');
    }
}
