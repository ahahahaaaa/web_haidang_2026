<div class="space-y-6">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="space-y-2">
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Công thức &amp; quy đổi dự toán</h1>
            <p class="max-w-3xl text-sm leading-7 text-zinc-500 dark:text-zinc-400">
                Màn hình này ghi trực tiếp vào bộ config estimator tại <span class="font-mono text-xs">skills/cost-estimator</span> để frontsite dùng đúng công thức, hệ số quy đổi, level input và bảng giá.
                Giữ nguyên machine key khi chỉnh sửa để tránh lệch dữ liệu giữa landing page, email và collection admin.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
        <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Thành phần quy đổi</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count($form['components'] ?? []) }}</p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Các dòng tính diện tích quy đổi theo công thức.</p>
        </div>
        <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Gói đơn giá</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count($form['packages'] ?? []) }}</p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Bảng đơn giá dùng để tính thành tiền từ m2 quy đổi.</p>
        </div>
        <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Cấp dự toán</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count($form['levels'] ?? []) }}</p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Mỗi level điều khiển phạm vi field hiển thị ở frontsite.</p>
        </div>
            <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Field catalog</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count($form['field_entries'] ?? []) }}</p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Quản lý type, required levels và nội dung hướng dẫn dùng chung cho tooltip + popup frontsite.</p>
            </div>
    </div>

    @if ($errors->has('form'))
        <div class="rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm leading-7 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200">
            {{ $errors->first('form') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Formula set</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Quản lý meta và các biểu thức dẫn xuất dùng chung trước khi tính từng component.</p>
                </div>
                <p class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    File: <span class="font-mono">examples/formula.json</span>
                </p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                    <input type="text" wire:model.defer="form.formula_meta.code" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.formula_meta.code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên formula set</span>
                    <input type="text" wire:model.defer="form.formula_meta.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.formula_meta.name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Version</span>
                    <input type="text" wire:model.defer="form.formula_meta.version" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.formula_meta.version') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Supports levels</span>
                    <input type="text" wire:model.defer="form.formula_meta.supports_levels_text" placeholder="level_1, level_2, level_3" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.formula_meta.supports_levels_text') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-8 space-y-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Biểu thức dẫn xuất</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Dùng cho footprint, diện tích mái hiệu dụng, diện tích hầm và các biến dẫn xuất khác.</p>
                    </div>
                    <button type="button" wire:click="addDerivedRow" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                        Thêm biểu thức
                    </button>
                </div>

                <div class="space-y-4">
                    @foreach (($form['derived_rows'] ?? []) as $index => $row)
                        <div wire:key="derived-row-{{ $index }}" class="grid gap-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 md:grid-cols-[0.35fr_1fr_auto]">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Key</span>
                                <input type="text" wire:model.defer="form.derived_rows.{{ $index }}.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.derived_rows.$index.key") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Expression</span>
                                <input type="text" wire:model.defer="form.derived_rows.{{ $index }}.expression" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.derived_rows.$index.expression") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-end">
                                <button type="button" wire:click="removeDerivedRow({{ $index }})" class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:border-red-300 dark:border-red-500/30 dark:text-red-300">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Thành phần quy đổi</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Quản lý từng dòng bóc tách diện tích quy đổi: label, area source, điều kiện và loại hệ số.</p>
                </div>
                <button type="button" wire:click="addComponent" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                    Thêm thành phần
                </button>
            </div>

            <div class="mt-5 space-y-4">
                @foreach (($form['components'] ?? []) as $index => $component)
                    <div wire:key="component-row-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-red-600">Thành phần {{ $loop->iteration }}</p>
                            <button type="button" wire:click="removeComponent({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition hover:border-red-300 dark:border-red-500/30 dark:text-red-300">
                                Xóa
                            </button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Key</span>
                                <input type="text" wire:model.defer="form.components.{{ $index }}.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.components.$index.key") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Section</span>
                                <input type="text" wire:model.defer="form.components.{{ $index }}.section" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.components.$index.section") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Label</span>
                                <input type="text" wire:model.defer="form.components.{{ $index }}.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.components.$index.label") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Area source</span>
                                <input type="text" wire:model.defer="form.components.{{ $index }}.area" placeholder="base_area hoặc biểu thức area" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.components.$index.area") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Điều kiện hiển thị</span>
                                <input type="text" wire:model.defer="form.components.{{ $index }}.condition" placeholder="Có thể để trống" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.components.$index.condition") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 xl:grid-cols-[0.28fr_0.72fr]">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Loại hệ số</span>
                                <select wire:model.defer="form.components.{{ $index }}.coefficient_mode" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="fixed">Hệ số cố định</option>
                                    <option value="expression">Biểu thức hệ số</option>
                                    <option value="option">Map theo option</option>
                                </select>
                                @error("form.components.$index.coefficient_mode") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                @if (($form['components'][$index]['coefficient_mode'] ?? 'fixed') === 'fixed')
                                    <label class="space-y-2 md:col-span-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Hệ số cố định</span>
                                        <input type="text" wire:model.defer="form.components.{{ $index }}.coefficient" placeholder="Ví dụ 0.7" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                        @error("form.components.$index.coefficient") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                @elseif (($form['components'][$index]['coefficient_mode'] ?? 'fixed') === 'expression')
                                    <label class="space-y-2 md:col-span-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Biểu thức hệ số</span>
                                        <input type="text" wire:model.defer="form.components.{{ $index }}.coefficient_expression" placeholder="Ví dụ void_area &lt; 8 ? 1 : 0.5" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                        @error("form.components.$index.coefficient_expression") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                @else
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Option field</span>
                                        <input type="text" wire:model.defer="form.components.{{ $index }}.option_field" placeholder="Ví dụ roof_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                        @error("form.components.$index.option_field") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Map option = hệ số</span>
                                        <textarea rows="4" wire:model.defer="form.components.{{ $index }}.coefficient_options_text" placeholder="btct_no_tile=0.5&#10;btct_tile=0.7" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                        @error("form.components.$index.coefficient_options_text") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Price set</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Bảng đơn giá quy đổi từ tổng m2 sang mức giá từng gói.</p>
                </div>
                <p class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    File: <span class="font-mono">configs/default-pricing.json</span>
                </p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                    <input type="text" wire:model.defer="form.pricing_meta.code" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.pricing_meta.code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Version</span>
                    <input type="text" wire:model.defer="form.pricing_meta.version" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.pricing_meta.version') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-8 space-y-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Danh sách gói giá</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Đơn vị đầu vào giữ theo số thuần, hệ thống sẽ format ở email và admin result.</p>
                    </div>
                    <button type="button" wire:click="addPackage" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                        Thêm gói giá
                    </button>
                </div>

                <div class="space-y-4">
                    @foreach (($form['packages'] ?? []) as $index => $package)
                        <div wire:key="package-row-{{ $index }}" class="grid gap-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 md:grid-cols-[0.22fr_0.38fr_0.2fr_0.14fr_auto]">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Key</span>
                                <input type="text" wire:model.defer="form.packages.{{ $index }}.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.packages.$index.key") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Label</span>
                                <input type="text" wire:model.defer="form.packages.{{ $index }}.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.packages.$index.label") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Đơn giá</span>
                                <input type="text" wire:model.defer="form.packages.{{ $index }}.unit_price" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.packages.$index.unit_price") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tiền tệ</span>
                                <input type="text" wire:model.defer="form.packages.{{ $index }}.currency" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.packages.$index.currency") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-end">
                                <button type="button" wire:click="removePackage({{ $index }})" class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:border-red-300 dark:border-red-500/30 dark:text-red-300">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Level profile</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Mỗi level là một profile input riêng. Danh sách field nhập theo từng level ghi mỗi dòng một key.</p>
                </div>
                <p class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    File: <span class="font-mono">configs/estimation-levels.json</span>
                </p>
            </div>

            <div class="mt-5 space-y-4">
                @foreach (($form['levels'] ?? []) as $index => $level)
                    <div wire:key="level-row-{{ $level['code'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                                <input type="text" wire:model.defer="form.levels.{{ $index }}.code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.levels.$index.code") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên level</span>
                                <input type="text" wire:model.defer="form.levels.{{ $index }}.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.levels.$index.name") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Extends</span>
                                <input type="text" wire:model.defer="form.levels.{{ $index }}.extends" placeholder="Có thể để trống" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @error("form.levels.$index.extends") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 md:col-span-2 xl:col-span-4">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Mục đích</span>
                                <textarea rows="2" wire:model.defer="form.levels.{{ $index }}.purpose" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                @error("form.levels.$index.purpose") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="space-y-2 md:col-span-2 xl:col-span-4">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Fields hiển thị</span>
                                <textarea rows="6" wire:model.defer="form.levels.{{ $index }}.fields_text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                @error("form.levels.$index.fields_text") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Field groups</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Nhóm field để chia tab hoặc block ở frontsite. Mỗi dòng là một field key.</p>
                    </div>
                    <p class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        File: <span class="font-mono">configs/field-catalog.json</span>
                    </p>
                </div>

                <div class="mt-5 space-y-4">
                    @foreach (($form['field_groups'] ?? []) as $index => $group)
                        <div wire:key="group-row-{{ $group['key'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div class="grid gap-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Group key</span>
                                        <input type="text" wire:model.defer="form.field_groups.{{ $index }}.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                        @error("form.field_groups.$index.key") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Label</span>
                                        <input type="text" wire:model.defer="form.field_groups.{{ $index }}.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                        @error("form.field_groups.$index.label") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Danh sách field trong group</span>
                                    <textarea rows="6" wire:model.defer="form.field_groups.{{ $index }}.fields_text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                    @error("form.field_groups.$index.fields_text") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Field catalog</h2>
                <p class="mt-1 text-sm leading-7 text-zinc-500 dark:text-zinc-400">
                    Phần này quản lý kiểu dữ liệu, required levels và nội dung hướng dẫn của từng field. Tooltip nhỏ ngay tại form dự toán và popup "Hướng dẫn nhập liệu" ngoài frontsite sẽ dùng chung dữ liệu này.
                </p>

                <div class="mt-5 space-y-4">
                    @foreach (($form['field_entries'] ?? []) as $index => $field)
                        <div wire:key="field-entry-{{ $field['key'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div class="flex flex-col gap-3 border-b border-zinc-200/80 pb-4 dark:border-zinc-800/80 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $field['key'] ?? 'field_'.$index }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $field['guide_title'] ?: 'Chưa đặt tiêu đề hướng dẫn' }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    wire:click="openGuideEditor({{ $index }})"
                                    class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $activeGuideEditorIndex === $index ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200' : 'border-zinc-300 text-zinc-700 hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300' }}"
                                >
                                    {{ $activeGuideEditorIndex === $index ? 'Đang chỉnh hướng dẫn' : 'Mở hướng dẫn' }}
                                </button>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-[0.24fr_0.16fr_0.24fr_0.36fr]">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Field key</span>
                                    <input type="text" wire:model.defer="form.field_entries.{{ $index }}.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    @error("form.field_entries.$index.key") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Type</span>
                                    <input type="text" wire:model.defer="form.field_entries.{{ $index }}.type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    @error("form.field_entries.$index.type") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Required levels</span>
                                    <input type="text" wire:model.defer="form.field_entries.{{ $index }}.required_levels_text" placeholder="level_1, level_2" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    @error("form.field_entries.$index.required_levels_text") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tiêu đề hướng dẫn</span>
                                    <input type="text" wire:model.defer="form.field_entries.{{ $index }}.guide_title" placeholder="Ví dụ: Có tầng hầm là gì?" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    @error("form.field_entries.$index.guide_title") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Nội dung hướng dẫn dùng chung</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Tooltip nhỏ và popup Hướng dẫn ngoài frontsite sẽ dùng chung nội dung này.</span>
                                </div>

                                @if ($activeGuideEditorIndex === $index)
                                    <x-admin.quill-editor
                                        wire:key="estimate-field-guide-{{ $field['key'] ?? $index }}"
                                        model="form.field_entries.{{ $index }}.guide_content"
                                        :value="$field['guide_content'] ?? ''"
                                        mode="rich"
                                        :allow-images="true"
                                        rows="8"
                                        placeholder="Diễn giải cho khách hàng hiểu field này dùng để làm gì, khi nào nên chọn và cách nhập đúng."
                                    />
                                @else
                                    <div class="rounded-3xl border border-dashed border-zinc-300 bg-white/80 px-4 py-3 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
                                        {{ \Illuminate\Support\Str::limit(trim(strip_tags((string) ($field['guide_content'] ?? ''))) ?: 'Chưa có nội dung hướng dẫn cho field này.', 220) }}
                                    </div>
                                @endif

                                @error("form.field_entries.$index.guide_content") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                <i class="fa-solid fa-floppy-disk"></i>
                Lưu cấu hình dự toán
            </button>
        </div>
    </form>
</div>
