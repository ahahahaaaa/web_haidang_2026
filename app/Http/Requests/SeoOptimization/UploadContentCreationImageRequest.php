<?php

namespace App\Http\Requests\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UploadContentCreationImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->attributes->get('seo_optimization_credential');

        return $credential instanceof SeoOptimizationCredential && $this->user() instanceof User
            && (string) $credential->user_id === (string) $this->user()->id
            && in_array('create', $credential->abilities ?? [], true)
            && $this->user()->can('admin.media.index');
    }

    public function rules(): array
    {
        return [
            'lease_token' => ['required', 'string', 'size:64'],
            'reference' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:10240'],
            'alt' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'prompt' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
