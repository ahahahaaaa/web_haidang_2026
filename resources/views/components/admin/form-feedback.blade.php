@props([
    'status' => session('status'),
])

@php
    $errorMessages = collect($errors->all())
        ->filter(fn ($message) => filled($message))
        ->values();
@endphp

@if (filled($status))
    <div class="hidden" data-admin-status-message="{{ $status }}"></div>
@endif

@if ($errorMessages->isNotEmpty())
    <div
        class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100"
        data-admin-error-summary="{{ $errorMessages->first() }}"
        data-admin-error-count="{{ $errorMessages->count() }}"
    >
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-base"></i>

            <div class="space-y-2">
                <p class="font-semibold">Biểu mẫu còn dữ liệu cần kiểm tra.</p>

                <div class="space-y-1.5">
                    @foreach ($errorMessages->take(3) as $message)
                        <p class="flex items-start gap-2">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-current/70"></span>
                            <span>{{ $message }}</span>
                        </p>
                    @endforeach
                </div>

                @if ($errorMessages->count() > 3)
                    <p class="text-xs text-amber-800/80 dark:text-amber-100/80">
                        Còn {{ $errorMessages->count() - 3 }} lỗi khác trong biểu mẫu.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
