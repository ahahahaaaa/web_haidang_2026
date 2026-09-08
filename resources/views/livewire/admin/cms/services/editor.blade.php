<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập dịch vụ' : 'Tạo dịch vụ' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang detail riêng cho dịch vụ giúp xử lý hero, gallery, SEO và nội dung dài tập trung hơn.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.services') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh sách
            </a>
            @if ($selectedId)
                <button type="button" wire:click="deleteService({{ $selectedId }})" wire:confirm="Bạn có chắc chắn muốn xóa dịch vụ này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa dịch vụ
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.services-submenu')

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit.prevent="saveService" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu dịch vụ...">
            @include('livewire.admin.cms.partials.service-basics')
            @include('livewire.admin.cms.partials.service-hero-slider')
            @include('livewire.admin.cms.partials.service-feature-blocks')
            @include('livewire.admin.cms.partials.service-gallery')
            @include('livewire.admin.cms.partials.service-process-block')
            @include('livewire.admin.cms.partials.service-pricing-block')
            @include('livewire.admin.cms.partials.geo-config-fields', [
                'path' => 'form.geo_config',
            ])
            @include('livewire.admin.cms.partials.faq-related-fields', [
                'faqTitle' => 'FAQ dịch vụ',
                'faqDescription' => 'Các câu hỏi này sẽ hiển thị ở cuối trang chi tiết dịch vụ và được dùng cho FAQ schema ngoài frontsite.',
                'relatedTitle' => 'Câu hỏi liên quan của dịch vụ',
                'relatedDescription' => 'Dùng cho khối “câu hỏi liên quan” để mở rộng intent tìm kiếm và gợi ý bước đọc tiếp theo.',
            ])
            @include('livewire.admin.cms.partials.service-seo')

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveService" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveService"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveService"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
