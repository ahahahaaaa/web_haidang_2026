<?php

namespace Src\Domains\Cms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicUrlMapping extends Model
{
    public const MODE_RENDER = 'render_target';

    public const MODE_REDIRECT = 'redirect';

    protected $fillable = [
        'source_hash',
        'source_path',
        'mode',
        'target_type',
        'target_id',
        'target_path',
        'status_code',
        'is_active',
        'origin',
        'context_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'context_json' => 'array',
            'is_active' => 'boolean',
            'status_code' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
