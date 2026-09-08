@if (session('status'))
    <div role="status" data-admin-status-message="{{ session('status') }}" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div role="alert" data-admin-error-summary class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
        <p class="font-semibold">Vui lòng kiểm tra trước khi tiếp tục:</p>
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $index => $error)
                <li wire:key="seo-error-{{ $index }}">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
