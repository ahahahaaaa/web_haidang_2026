@props([
    'allowImages' => false,
    'mode' => 'plain',
    'model',
    'placeholder' => '',
    'rows' => 4,
    'value' => '',
])

@php
    $minHeight = max(((int) $rows) * 24, $mode === 'rich' ? 220 : 120);
@endphp

<div
    {{ $attributes->class('admin-quill-wrapper')->except('style') }}
    style="--admin-quill-min-height: {{ $minHeight }}px; {{ $attributes->get('style') }}"
>
    <div
        class="admin-quill"
        data-admin-quill
        data-allow-images="{{ $allowImages ? 'true' : 'false' }}"
        data-mode="{{ $mode }}"
        data-placeholder="{{ $placeholder }}"
    >
        <textarea class="hidden" data-quill-source wire:model.defer="{{ $model }}">{{ $value }}</textarea>
        <div class="admin-quill-toolbar" data-quill-toolbar></div>
        <div class="admin-quill-surface" data-quill-editor wire:ignore></div>
    </div>
</div>
