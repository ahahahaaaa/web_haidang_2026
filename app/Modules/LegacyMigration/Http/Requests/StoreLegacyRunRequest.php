<?php

namespace App\Modules\LegacyMigration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegacyRunRequest extends FormRequest
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
            'source_filename' => ['nullable', 'string', 'max:255'],
            'total_urls' => ['required', 'integer', 'min:0', 'max:1000000'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:100000'],
            'created_at' => ['nullable', 'date'],
        ];
    }
}
