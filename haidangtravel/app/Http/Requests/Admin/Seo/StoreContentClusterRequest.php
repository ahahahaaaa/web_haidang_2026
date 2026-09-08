<?php

namespace App\Http\Requests\Admin\Seo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Src\Domains\Seo\Enums\SeoPageType;

class StoreContentClusterRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('viewSeoAdmin') ?? false; }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'primary_keyword' => ['required','string','max:255'],
            'secondary_keywords' => ['nullable','array'],
            'secondary_keywords.*' => ['string','max:255'],
            'lsi_keywords' => ['nullable','array'],
            'lsi_keywords.*' => ['string','max:255'],
            'intent' => ['required','string','max:50'],
            'target_page_type' => ['required', new Enum(SeoPageType::class)],
            'business_value' => ['nullable','integer','between:1,10'],
            'priority_score' => ['nullable','integer','between:1,10'],
            'context' => ['nullable','array'],
            'dispatch_generation' => ['nullable','boolean'],
        ];
    }
}
