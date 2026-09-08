<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý tour</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tour trong nước, tour nước ngoài và tour đoàn đã chuẩn hóa theo domain travel mới.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách tour</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Mở tour để biên tập nhanh.</p>
                    </div>

                    <button type="button" wire:click="createTour" class="rounded-2xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                        Tour mới
                    </button>
                </div>

                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên tour..." class="mb-5 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <div class="space-y-3">
                    @foreach ($tours as $tour)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <button type="button" wire:click="editTour({{ $tour->id }})" class="text-left text-base font-semibold text-zinc-900 hover:text-teal-600 dark:text-white">
                                        {{ $tour->title }}
                                    </button>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">{{ $tour->scope->label() }}</span>
                                        @if ($tour->destination)
                                            <span class="rounded-full bg-teal-50 px-3 py-1 font-semibold text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">{{ $tour->destination->name }}</span>
                                        @endif
                                        @if ($tour->region)
                                            <span class="rounded-full bg-cyan-50 px-3 py-1 font-semibold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">{{ $tour->region->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button type="button" wire:click="deleteTour({{ $tour->id }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $tours->links() }}
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Điểm đến</h2>
                        @foreach ($destinations as $destination)
                            <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/70">
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $destination->name }}</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $destination->slug }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="editDestination({{ $destination->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                    <button type="button" wire:click="deleteDestination({{ $destination->id }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Vùng tour</h2>
                        @foreach ($regions as $region)
                            <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/70">
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $region->name }}</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $region->slug }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="editRegion({{ $region->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                    <button type="button" wire:click="deleteRegion({{ $region->id }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <form wire:submit="saveDestination" class="space-y-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Form điểm đến</h3>
                        <input type="text" wire:model.defer="destinationForm.name" placeholder="Tên điểm đến" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="destinationForm.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="number" wire:model.defer="destinationForm.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <button type="submit" class="rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white">{{ $editingDestinationId ? 'Cập nhật điểm đến' : 'Thêm điểm đến' }}</button>
                    </form>

                    <form wire:submit="saveRegion" class="space-y-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Form vùng tour</h3>
                        <input type="text" wire:model.defer="regionForm.name" placeholder="Tên vùng" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="regionForm.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="number" wire:model.defer="regionForm.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <button type="submit" class="rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white">{{ $editingRegionId ? 'Cập nhật vùng' : 'Thêm vùng' }}</button>
                    </form>
                </div>
            </section>
        </div>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="mb-5 text-lg font-semibold text-zinc-900 dark:text-white">Biên tập tour</h2>

            <form wire:submit="saveTour" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.title" placeholder="Tên tour" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <input type="text" wire:model.defer="form.slug" placeholder="Slug" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <select wire:model.defer="form.status" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="draft">draft</option>
                        <option value="published">published</option>
                        <option value="archived">archived</option>
                    </select>
                    <select wire:model.defer="form.scope" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @foreach ($scopeOptions as $scopeOption)
                            <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                        @endforeach
                    </select>
                    <select wire:model.defer="form.destination_category_id" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Điểm đến</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.defer="form.region_category_id" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Vùng tour</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.defer="form.transport" placeholder="Phương tiện" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.departure_location" placeholder="Điểm khởi hành" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="form.duration_days" placeholder="Số ngày" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="form.duration_nights" placeholder="Số đêm" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.standard_label" placeholder="Tiêu chuẩn" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="form.base_price" placeholder="Giá gốc" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="form.sale_price" placeholder="Giá bán / sale" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" step="0.1" wire:model.defer="form.rating_average" placeholder="Rating average" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="form.rating_count" placeholder="Rating count" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <select wire:model.defer="form.cta_mode" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="book">book</option>
                        <option value="contact">contact</option>
                    </select>
                    <input type="number" wire:model.defer="form.sort_order" placeholder="Thứ tự" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="url" wire:model.defer="form.cover_image_url" placeholder="Cover image URL" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <input type="text" wire:model.defer="form.cover_alt" placeholder="Alt text cover" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-2">
                        <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-teal-600">
                        Nổi bật
                    </label>
                </div>

                <x-admin.quill-editor wire:key="tour-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt tour" />
                <x-admin.quill-editor wire:key="tour-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="10" placeholder="Nội dung chi tiết" />

                @include('livewire.admin.cms.partials.tour-itinerary-pricing-fields')
                <textarea rows="3" wire:model.defer="form.departure_text" placeholder="Ngày khởi hành, mỗi dòng một ngày" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="3" wire:model.defer="form.inclusions_text" placeholder="Bao gồm, mỗi dòng một mục" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="3" wire:model.defer="form.faq_text" placeholder="FAQ, mỗi dòng: Câu hỏi | Trả lời" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.meta_title" placeholder="Meta title" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.og_title" placeholder="OG title" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="url" wire:model.defer="form.canonical_url" placeholder="Canonical URL" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.robots_directive" placeholder="Robots" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <textarea rows="3" wire:model.defer="form.meta_description" placeholder="Meta description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="3" wire:model.defer="form.og_description" placeholder="OG description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>

                <button type="submit" class="rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3 text-sm font-semibold text-white">
                    Lưu tour
                </button>
            </form>
        </section>
    </div>
</div>
