<?php

namespace App\Http\Requests\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use Illuminate\Foundation\Http\FormRequest;

class CompleteSeoOptimizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->attributes->get('seo_optimization_credential');

        return $credential instanceof SeoOptimizationCredential && in_array('automate', $credential->abilities ?? [], true);
    }

    public function rules(): array
    {
        return ['proposal_id' => ['required', 'ulid'], 'content_hash' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/']];
    }
}
