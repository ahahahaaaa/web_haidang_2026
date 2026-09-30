<?php

namespace App\Modules\LegacyMigration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegacyChunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schema_version' => ['required', 'string', Rule::in([(string) config('legacy_migration.schema')])],
            'source_system' => ['required', 'string', 'max:80', Rule::in((array) config('legacy_migration.allowed_source_systems'))],
            'source_run_id' => ['required', 'uuid'],
            'chunk' => ['required', 'array'],
            'chunk.uuid' => ['required', 'uuid'],
            'chunk.sequence' => ['required', 'integer', 'min:1'],
            'chunk.idempotency_key' => ['required', 'string', 'max:190'],
            'urls' => ['present', 'array', 'max:'.max(1, (int) config('legacy_migration.max_urls_per_chunk'))],
            'urls.*.raw_url' => ['nullable', 'string', 'max:1000'],
            'urls.*.raw_path' => ['required', 'string', 'max:700'],
            'urls.*.normalized_path' => ['required', 'string', 'max:700'],
            'urls.*.resolved_path' => ['nullable', 'string', 'max:700'],
            'urls.*.route_kind' => ['required', 'string', Rule::in((array) config('legacy_migration.supported_route_kinds'))],
            'urls.*.root' => ['nullable', 'string', 'max:255'],
            'urls.*.metrics' => ['nullable', 'array'],
            'urls.*.metrics.clicks' => ['nullable', 'integer', 'min:0'],
            'urls.*.metrics.impressions' => ['nullable', 'integer', 'min:0'],
            'urls.*.metrics.ctr' => ['nullable', 'numeric', 'min:0'],
            'urls.*.metrics.position' => ['nullable', 'numeric', 'min:0'],
            'objects' => ['present', 'array', 'max:'.max(1, (int) config('legacy_migration.max_objects_per_chunk'))],
            'objects.*.key' => ['required', 'string', 'max:255', 'regex:/^[a-z_]+:[^:\s]+$/'],
            'objects.*.type' => ['required', 'string', Rule::in((array) config('legacy_migration.supported_object_types'))],
            'objects.*.legacy_id' => ['nullable'],
            'objects.*.legacy_key' => ['nullable', 'string', 'max:255'],
            'objects.*.partial' => ['sometimes', 'boolean'],
            'objects.*.attributes' => ['present', 'array'],
            'objects.*.relationships' => ['present', 'array'],
            'objects.*.media' => ['present', 'array'],
            'objects.*.checksum' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
            'payload_hash' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
        ];
    }
}
