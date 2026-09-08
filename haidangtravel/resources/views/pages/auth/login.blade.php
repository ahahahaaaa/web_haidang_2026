@php
    $siteSettings = app(\App\Services\Cms\SiteSettingsManager::class)->current();
    $companyName = trim((string) ($siteSettings->company_name ?: $siteSettings->site_name ?: 'Haidang Travel'));
    $contactPhone = trim((string) ($siteSettings->hotline ?: $siteSettings->phone));
    $contactEmail = trim((string) ($siteSettings->primary_email ?: $siteSettings->support_email));
    $companyAddress = trim((string) $siteSettings->address);
@endphp

<x-layouts::auth title="Đăng nhập CMS">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Đăng nhập CMS"
            description="Sử dụng tài khoản quản trị của {{ $companyName }}."
        />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Email quản trị"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="admin@haidangtravel.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Mật khẩu"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Quên mật khẩu?
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Ghi nhớ đăng nhập" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Đăng nhập
                </flux:button>
            </div>
        </form>

        @if ($contactPhone !== '' || $contactEmail !== '' || $companyAddress !== '')
            <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 text-sm text-zinc-600 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/70 dark:text-zinc-300">
                <p class="font-semibold text-zinc-900 dark:text-white">Thông tin công ty</p>
                <dl class="mt-3 space-y-2">
                    @if ($contactPhone !== '')
                        <div class="flex gap-2">
                            <dt class="shrink-0 font-medium text-zinc-700 dark:text-zinc-200">Hotline:</dt>
                            <dd>{{ $contactPhone }}</dd>
                        </div>
                    @endif

                    @if ($contactEmail !== '')
                        <div class="flex gap-2">
                            <dt class="shrink-0 font-medium text-zinc-700 dark:text-zinc-200">Email:</dt>
                            <dd class="break-all">{{ $contactEmail }}</dd>
                        </div>
                    @endif

                    @if ($companyAddress !== '')
                        <div class="flex gap-2">
                            <dt class="shrink-0 font-medium text-zinc-700 dark:text-zinc-200">Địa chỉ:</dt>
                            <dd>{{ $companyAddress }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>Chưa có tài khoản?</span>
                <flux:link :href="route('register')" wire:navigate>Đăng ký</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
