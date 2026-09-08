<?php

namespace App\Livewire\Admin\Cms\Concerns;

use App\Support\RichText;

trait InteractsWithEditorContent
{
    protected function plainEditorContent(?string $value): string
    {
        return RichText::normalizePlain($value);
    }

    protected function richEditorContent(?string $value): string
    {
        return RichText::sanitize($value);
    }
}
