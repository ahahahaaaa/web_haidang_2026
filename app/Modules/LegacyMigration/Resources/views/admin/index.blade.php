<div class="space-y-4">
    <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Chuyển dữ liệu website cũ</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Module tạm: nhận staging trước, chọn đúng URL/page rồi mới cast vào CMS.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($run)
                <flux:button :href="route('admin.legacy-migration.index')" wire:navigate variant="ghost">Tất cả phiên</flux:button>
            @endif
            <flux:button :href="route('admin.legacy-migration.redirects.export')">Export redirect CSV</flux:button>
        </div>
    </header>

    @if (! config('legacy_migration.enabled'))
        <flux:callout variant="warning" heading="Receiver đang tắt">
            Bật <code>LEGACY_MIGRATION_ENABLED=true</code> và cấu hình token hash trước khi website cũ gửi dữ liệu.
        </flux:callout>
    @endif

    @if (session('status'))
        <flux:callout variant="success" heading="Hoàn tất">{{ session('status') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" heading="Không thể thực hiện">{{ session('error') }}</flux:callout>
    @endif

    @if (! $run)
        <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Các phiên đã nhận</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Receiver không sửa dữ liệu CMS trong giai đoạn này.</p>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr><th class="px-4 py-3">Phiên</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Chunks</th><th class="px-4 py-3">URLs</th><th class="px-4 py-3">Bắt đầu</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($runs as $item)
                            <tr wire:key="legacy-run-{{ $item->id }}">
                                <td class="px-4 py-3">
                                    <a wire:navigate href="{{ route('admin.legacy-migration.runs.show', $item) }}" class="font-semibold text-teal-700 hover:underline dark:text-teal-300">{{ $item->source_filename ?: $item->source_run_id }}</a>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $item->source_system }} · {{ $item->source_run_id }}</p>
                                </td>
                                <td class="px-4 py-3"><flux:badge>{{ $item->status }}</flux:badge></td>
                                <td class="px-4 py-3">{{ number_format($item->received_chunks) }} / {{ number_format($item->expected_chunks) }}</td>
                                <td class="px-4 py-3">{{ number_format($item->staged_urls) }} / {{ number_format($item->expected_urls) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ optional($item->started_at)->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">Chưa nhận phiên migrate nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $runs->links() }}
        </section>
    @else
        <section
            @if (! $mappingModalOpen && ! $bulkProcessingModalOpen)
                wire:poll.10s
            @endif
            class="grid grid-cols-2 gap-3 lg:grid-cols-6"
        >
            @foreach ([
                'staged_urls' => 'Đã staging',
                'mapped_urls' => 'Đã mapping',
                'casted_urls' => 'Đã cast',
                'blocked_urls' => 'Blocked',
                'failed_urls' => 'Lỗi',
            ] as $field => $label)
                <div wire:key="legacy-stat-{{ $field }}" class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($run->{$field}) }}</p>
                </div>
            @endforeach
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Trạng thái phiên</p>
                <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ $run->status }}</p>
            </div>
        </section>

        @if ($run->finalized_at && filled($run->error_text))
            <flux:callout variant="warning" heading="Phiên đã finalize với cảnh báo đối soát">
                <p class="break-words">{{ $run->error_text }}</p>
                <p class="mt-1 text-xs">Hệ thống chỉ xử lý các URL đã staging; số khai báo ban đầu vẫn được giữ để đối chiếu.</p>
            </flux:callout>
        @endif

        @if ($failureGroups->isNotEmpty())
            <section class="space-y-3 rounded-3xl border border-red-200 bg-red-50/60 p-4 dark:border-red-900 dark:bg-red-950/20">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="font-semibold text-red-900 dark:text-red-100">Nhóm lỗi xử lý hiện tại</h2>
                        <p class="text-xs text-red-700 dark:text-red-300">Tổng hợp tối đa 10 nguyên nhân từ các URL đang có trạng thái failed. Lịch sử từng lần chạy vẫn được giữ trong audit.</p>
                        <p class="text-xs text-red-700 dark:text-red-300">Bấm vào một nhóm lỗi để lọc các URL tương ứng trong danh sách bên dưới.</p>
                    </div>
                    <flux:button type="button" size="sm" wire:click="filterFailedUrls">Xem tất cả URL lỗi</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($failureGroups as $failureGroup)
                        <div wire:key="legacy-failure-group-{{ md5((string) $failureGroup->error_text) }}" class="rounded-2xl border border-red-200 bg-white dark:border-red-900 dark:bg-zinc-900">
                            <flux:button
                                type="button"
                                variant="ghost"
                                align="start"
                                class="h-auto! w-full whitespace-normal! p-3! text-left"
                                wire:click="filterFailureGroup({{ $failureGroup->sample_url_id }}, '{{ hash('sha256', (string) $failureGroup->error_text) }}')"
                                wire:loading.attr="disabled"
                                aria-pressed="{{ $failureFilter === (string) $failureGroup->error_text ? 'true' : 'false' }}"
                                title="Lọc các URL có lỗi này"
                            >
                                <span class="flex w-full flex-col gap-2 md:flex-row md:items-start">
                                    <flux:badge color="red">{{ number_format($failureGroup->total) }} URL</flux:badge>
                                    <span class="min-w-0 flex-1 break-words text-sm text-red-800 dark:text-red-200">{{ \Illuminate\Support\Str::limit((string) $failureGroup->error_text, 500) }}</span>
                                    <span class="shrink-0 text-xs text-red-600 dark:text-red-300">{{ $failureFilter === (string) $failureGroup->error_text ? 'Đang lọc' : 'Xem URL' }}</span>
                                </span>
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="space-y-4 rounded-3xl border border-violet-200 bg-violet-50/40 p-4 dark:border-violet-900 dark:bg-violet-950/10">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Xử lý tự động theo loại</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi URL chạy bằng queue riêng. URL trùng cùng object sẽ dùng chung một bản ghi CMS, không tạo lặp.</p>
                </div>
                <flux:badge color="violet">{{ number_format($automaticEligibleCount) }} URL phù hợp</flux:badge>
            </div>

            @if ($run->status === 'receiving')
                <flux:callout variant="warning" heading="Đang chờ finalize">
                    Có thể chọn trước setting, nhưng nút xử lý chỉ hoạt động sau khi website cũ gửi đủ chunk và finalize phiên.
                </flux:callout>
            @endif

            <form wire:submit="queueAutomaticProcessing" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <flux:select label="Loại dữ liệu nguồn" wire:model.live="automaticSourceType">
                        @foreach ($automaticSourceTypes as $type => $label)
                            <option wire:key="legacy-auto-type-{{ $type }}" value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Cách tìm / tạo đích" wire:model.live="automaticStrategy">
                        @foreach ($automaticStrategies as $strategy => $label)
                            <option wire:key="legacy-auto-strategy-{{ $strategy }}" value="{{ $strategy }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Sau khi cast" wire:model="automaticMappingMode">
                        <option value="cast_and_redirect">Cast + redirect 301 (khuyến nghị)</option>
                        <option value="cast_preserve_url">Cast + giữ URL cũ trả 200</option>
                        <option value="cast_only">Chỉ cast, chưa redirect</option>
                    </flux:select>
                    <flux:select label="Chính sách field" wire:model="automaticMergePolicy">
                        <option value="overwrite">Ghi dữ liệu nguồn vào field whitelist</option>
                        <option value="fill_blanks">Chỉ điền field đang trống</option>
                    </flux:select>
                </div>

                <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 dark:bg-zinc-900 md:flex-row md:items-center md:justify-between">
                    <div>
                        <flux:checkbox wire:model="automaticImportMedia" label="Tải ảnh blog về Media Library và đổi URL ảnh trong nội dung" :disabled="$automaticSourceType !== 'blog'" />
                        <flux:error name="automaticImportMedia" />
                        <p class="mt-1 text-xs text-zinc-500">Ảnh đầu tiên được dùng làm cover nếu bài chưa có cover. Mặc định URL nguồn redirect 301 tới URL CMS; trang đích canonical về chính nó.</p>
                        <p class="mt-1 text-xs text-zinc-500">Giới hạn hiện tại: {{ max(1, (int) config('legacy_migration.media.max_images_per_object', 100)) }} ảnh/bài.</p>
                    </div>
                    <flux:button
                        type="submit"
                        variant="primary"
                        color="violet"
                        wire:loading.attr="disabled"
                        wire:target="queueAutomaticProcessing"
                        :disabled="$run->status === 'receiving' || $automaticEligibleCount === 0"
                        wire:confirm="Đưa {{ number_format($automaticEligibleCount) }} URL loại {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }} vào queue theo setting đang chọn?"
                    >
                        <span wire:loading.remove wire:target="queueAutomaticProcessing">Tự động xử lý {{ number_format($automaticEligibleCount) }} URL</span>
                        <span wire:loading wire:target="queueAutomaticProcessing">Đang tạo hàng đợi...</span>
                    </flux:button>
                </div>
            </form>
        </section>

        @if ($preservedUrlRedirectCount > 0)
            <section class="space-y-3 rounded-3xl border border-sky-200 bg-sky-50/50 p-4 dark:border-sky-900 dark:bg-sky-950/10">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Hợp nhất URL đã cast</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">
                            Có {{ number_format($preservedUrlRedirectCount) }} URL nguồn đang trả 200. Chuyển chúng thành redirect 301 về target CMS hiện có và đặt canonical của target về chính URL CMS.
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Chỉ thay đổi mapping URL và canonical. Không import lại, không tạo blog mới, không đổi content, media, <code>created_at</code> hoặc <code>updated_at</code>.
                            Mỗi URL commit độc lập; nếu request bị ngắt giữa chừng, tải lại trang và bấm tiếp, URL đã chuyển sẽ không chạy lại.
                        </p>
                    </div>
                    <flux:button
                        type="button"
                        variant="primary"
                        color="sky"
                        wire:click="convertPreservedUrlsToRedirects"
                        wire:loading.attr="disabled"
                        wire:target="convertPreservedUrlsToRedirects"
                        :disabled="$run->status === 'receiving'"
                        wire:confirm="Chuyển {{ number_format($preservedUrlRedirectCount) }} URL nguồn đang trả 200 thành redirect 301 về target hiện có? Nội dung, media và timestamp không bị xử lý lại."
                    >
                        <span wire:loading.remove wire:target="convertPreservedUrlsToRedirects">Chuyển {{ number_format($preservedUrlRedirectCount) }} URL sang 301</span>
                        <span wire:loading wire:target="convertPreservedUrlsToRedirects">Đang cập nhật mapping...</span>
                    </flux:button>
                </div>
                <flux:error name="preservedRedirect" />
            </section>
        @endif

        <section class="space-y-3 rounded-3xl border border-amber-200 bg-amber-50/40 p-4 dark:border-amber-900 dark:bg-amber-950/10">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Khôi phục hàng đợi bị kẹt</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Loại {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }} trong toàn bộ phiên, không giới hạn theo tìm kiếm trong list.
                        Connection: <code>{{ $queueRecovery['connection'] }}</code> · Queue: <code>{{ $queueRecovery['queue'] }}</code>.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:badge color="amber">{{ number_format($queueRecovery['queued']) }} chờ worker</flux:badge>
                    <flux:badge>{{ number_format($queueRecovery['processing']) }} đang xử lý</flux:badge>
                    <flux:badge>{{ number_format($queueRecovery['stale']) }} URL cần kiểm tra</flux:badge>
                </div>
            </div>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                Chỉ khôi phục URL queued/processing không cập nhật ít nhất {{ (int) ceil($queueRecovery['stale_seconds'] / 60) }} phút và không còn job trong queue.
                Job đang chờ, chạy hoặc hẹn chạy lại được giữ nguyên. Giữ target đã tạo, dữ liệu nguồn, timestamp nguồn và URL cũ; không tạo blog mới.
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Dùng mapping và chính sách field đã lưu của từng URL, không dùng lựa chọn tạo đích ở trên. Tải ảnh theo checkbox Media hiện tại.
                Nút chỉ đưa lại job; server vẫn cần worker chạy đúng connection/queue để có nội dung và page công khai.
            </p>
            @if ($queueRecovery['unavailable_reason'])
                <p class="text-sm text-amber-800 dark:text-amber-200">{{ $queueRecovery['unavailable_reason'] }}</p>
            @endif
            <flux:error name="queueRecovery" />
            <flux:button
                type="button"
                wire:click="recoverStuckUrls"
                wire:loading.attr="disabled"
                wire:target="recoverStuckUrls,recoverStuckUrl"
                :disabled="$run->status === 'receiving' || $queueRecovery['unavailable_reason'] !== null || $queueRecovery['stale'] === 0"
                wire:confirm="Kiểm tra và khôi phục URL bị kẹt loại {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }} trong phiên này? Giữ target cũ; URL vẫn có job sẽ được bỏ qua."
            >Khôi phục URL bị kẹt và xử lý lại</flux:button>
        </section>

        <flux:modal name="legacy-url-mapping" wire:model="mappingModalOpen" focusable class="max-w-5xl">
        @if ($selectedUrl)
            <section class="space-y-4">
                @if ($run->status === 'receiving')
                    <flux:callout variant="warning" heading="Chưa thể mapping">
                        Phiên vẫn đang nhận chunk. Hãy finalize thành công trước khi chọn page đích hoặc cast.
                    </flux:callout>
                @endif
                <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-300">URL đang mapping</p>
                        <h2 class="mt-1 break-all text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedUrl->normalized_path }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ $selectedUrl->route_kind }} · root: {{ $selectedUrl->root_object_key ?: 'không có' }}</p>
                    </div>
                    <div class="text-sm text-zinc-500">
                        <p>{{ number_format($selectedUrl->clicks) }} clicks · {{ number_format($selectedUrl->impressions) }} impressions</p>
                        <p class="mt-1">Trạng thái: <strong>{{ $selectedUrl->status }}</strong></p>
                    </div>
                </div>

                @if ($selectedUrl->error_text)
                    <flux:callout variant="danger" heading="Lỗi hiện tại của URL">
                        <p class="break-words">{{ $selectedUrl->error_text }}</p>
                    </flux:callout>
                @endif

                @if ($selectedUrl->latestMediaAudit?->status === 'warning')
                    <flux:callout variant="warning" heading="Ảnh cần kiểm tra — bài vẫn được xử lý">
                        <p>Ảnh URL không tải được đã bị bỏ khỏi content; ảnh vượt giới hạn, hết ngân sách tải, ảnh nhúng lỗi hoặc lỗi cấu hình CA trên server vẫn dùng placeholder. JSON nguồn giữ nguyên. Sau khi sửa nguồn ảnh, giữ target hiện tại và xử lý lại với chính sách ghi đè dữ liệu nguồn để khôi phục ảnh.</p>
                        @foreach (data_get($selectedUrl->latestMediaAudit->after_json, 'warnings', []) as $warningIndex => $imageWarning)
                            <details wire:ignore.self wire:key="legacy-image-warning-{{ $selectedUrl->latestMediaAudit->id }}-{{ $warningIndex }}">
                                <summary>{{ data_get($imageWarning, 'image_error_code') }} · {{ data_get($imageWarning, 'image_source') }}</summary>
                                <p>{{ data_get($imageWarning, 'content_action') === 'removed' ? 'Đã bỏ ảnh khỏi content; dữ liệu nguồn vẫn được giữ.' : 'Đã dùng placeholder để kiểm tra sau.' }}</p>
                                <p>{{ data_get($imageWarning, 'error_text') }}</p>
                                @if (data_get($imageWarning, 'detail'))
                                    <p>{{ data_get($imageWarning, 'detail') }}</p>
                                @endif
                            </details>
                        @endforeach
                    </flux:callout>
                @endif

                @if ($selectedFailureAudits->isNotEmpty())
                    <details wire:ignore.self class="rounded-2xl border border-red-200 p-3 dark:border-red-900">
                        <summary class="cursor-pointer text-sm font-semibold text-red-800 dark:text-red-200">Lịch sử {{ number_format($selectedFailureAudits->count()) }} lần lỗi gần nhất</summary>
                        <div class="mt-3 space-y-2">
                            @foreach ($selectedFailureAudits as $failureAudit)
                                <div wire:key="legacy-failure-audit-{{ $failureAudit->id }}" class="rounded-xl bg-red-50 p-3 text-sm dark:bg-red-950/30">
                                    <p class="text-xs text-red-600 dark:text-red-300">{{ optional($failureAudit->created_at)->format('d/m/Y H:i:s') }} · {{ $failureAudit->action }}</p>
                                    <p class="mt-1 break-words text-red-900 dark:text-red-100">{{ $failureAudit->error_text }}</p>
                                    @if (data_get($failureAudit->after_json, 'image_source'))
                                        <p>Ảnh nguồn: {{ data_get($failureAudit->after_json, 'image_source') }}</p>
                                        <p>Mã lỗi: {{ data_get($failureAudit->after_json, 'image_error_code') }} · {{ data_get($failureAudit->after_json, 'retryable') ? 'Có thể thử lại' : 'Cần sửa nguồn hoặc cấu hình trước khi thử lại' }}</p>
                                        @if (data_get($failureAudit->after_json, 'detail'))
                                            <p>{{ data_get($failureAudit->after_json, 'detail') }}</p>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif

                @if ($selectedObject)
                    <div class="grid gap-3 rounded-2xl bg-zinc-50 p-3 text-sm dark:bg-zinc-950 md:grid-cols-3">
                        <div><span class="text-zinc-500">Object</span><p class="font-semibold text-zinc-900 dark:text-white">{{ $selectedObject->object_type }} / {{ $selectedObject->object_key }}</p></div>
                        <div><span class="text-zinc-500">Ngày tạo nguồn</span><p class="font-semibold text-zinc-900 dark:text-white">{{ optional($selectedObject->source_created_at)->format('d/m/Y H:i:s') ?: 'Thiếu — không thể cast' }}</p></div>
                        <div><span class="text-zinc-500">Cập nhật nguồn</span><p class="font-semibold text-zinc-900 dark:text-white">{{ optional($selectedObject->source_updated_at)->format('d/m/Y H:i:s') ?: 'Dùng ngày tạo' }}</p></div>
                    </div>
                @endif

                <form wire:submit="saveAndProcessSelected" class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <flux:select label="Cách xử lý" wire:model.live="mappingMode">
                            <option value="cast_and_redirect">Cast + redirect URL cũ 301 (khuyến nghị)</option>
                            <option value="cast_preserve_url">Cast + giữ URL nguồn trả 200</option>
                            <option value="cast_only">Chỉ cast dữ liệu</option>
                            <option value="redirect_only">Chỉ redirect, không sửa page</option>
                            <option value="blocked">Block / bỏ qua</option>
                        </flux:select>
                        <flux:select label="Chính sách field" wire:model="mergePolicy" :disabled="$mappingMode === 'blocked' || $mappingMode === 'redirect_only'">
                            <option value="fill_blanks">Chỉ điền field đang trống</option>
                            <option value="overwrite">Ghi đè field được whitelist</option>
                        </flux:select>
                        <flux:select label="Loại page/model đích" wire:model.live="targetType" :disabled="$mappingMode === 'blocked'">
                            <option value="">Chọn loại dữ liệu</option>
                            @foreach ($targetTypes as $type => $label)
                                <option wire:key="legacy-target-type-{{ $type }}" value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select label="Mã redirect" wire:model="redirectCode" :disabled="! in_array($mappingMode, ['cast_and_redirect', 'redirect_only'], true)">
                            <option value="301">301 vĩnh viễn</option><option value="302">302 tạm thời</option><option value="307">307 tạm thời</option>
                        </flux:select>
                    </div>

                    @if ($mappingMode !== 'blocked' && $targetType !== '')
                        <div class="grid gap-3 md:grid-cols-2">
                            <flux:input label="Tìm page đích" wire:model.live.debounce.300ms="targetSearch" placeholder="Tên hoặc slug..." />
                            <flux:select label="Page/URL đích" wire:model="targetId">
                                <option value="">Chọn page tương ứng</option>
                                @foreach ($targetOptions as $option)
                                    <option wire:key="legacy-target-option-{{ $targetType }}-{{ $option['id'] }}" value="{{ $option['id'] }}">{{ $option['label'] }} · {{ $option['path'] }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    <flux:error name="targetType" />
                    <flux:error name="targetId" />
                    <flux:error name="mappingMode" />
                    <flux:callout variant="info" heading="Quy tắc URL">
                        Nội dung tốt chọn <strong>Cast + redirect URL cũ 301</strong> để hợp nhất tín hiệu vào URL CMS và canonical của trang đích. Chỉ dùng chế độ giữ URL trả 200 khi thật sự cần hai URL công khai riêng biệt.
                    </flux:callout>
                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="filled">Đóng</flux:button>
                        </flux:modal.close>
                        <flux:button type="button" wire:click="saveMapping" wire:loading.attr="disabled" wire:target="saveMapping" :disabled="$run->status === 'receiving'">
                            Chỉ lưu mapping
                        </flux:button>
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="saveAndProcessSelected"
                            :disabled="$run->status === 'receiving'"
                            wire:confirm="{{ $mappingMode === 'blocked' ? 'Đánh dấu bỏ qua URL này?' : ($mappingMode === 'redirect_only' ? 'Tạo redirect từ URL cũ tới page đã chọn và không thay đổi dữ liệu đích?' : 'Bạn xác nhận page đích là đúng đối tượng và cho phép áp dụng dữ liệu nguồn?') }}"
                        >
                            <span wire:loading.remove wire:target="saveAndProcessSelected">
                                {{ $mappingMode === 'blocked' ? 'Đánh dấu bỏ qua' : ($mappingMode === 'redirect_only' ? 'Lưu và tạo redirect' : 'Lưu và xử lý URL này') }}
                            </span>
                            <span wire:loading wire:target="saveAndProcessSelected">Đang xử lý...</span>
                        </flux:button>
                    </div>
                </form>

                @if ($selectedObject)
                    <details wire:ignore.self class="rounded-2xl border border-zinc-200 p-3 dark:border-zinc-800">
                        <summary class="cursor-pointer text-sm font-semibold text-zinc-700 dark:text-zinc-200">Xem JSON object nguồn</summary>
                        <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-all text-xs text-zinc-600 dark:text-zinc-300">{{ json_encode($selectedObject->payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </section>
        @endif
        </flux:modal>

        <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <flux:input label="Tìm URL / object key" wire:model.live.debounce.300ms="search" placeholder="Ví dụ: da-lat hoặc tour:125" />
                <flux:select label="Loại dữ liệu" wire:model.live="objectType">
                    <option value="">Tất cả loại</option>
                    @foreach ($automaticSourceTypes as $type => $label)
                        <option wire:key="legacy-filter-type-{{ $type }}" value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:select label="Trạng thái" wire:model.live="status">
                    <option value="">Tất cả</option>
                    @foreach (['pending', 'needs_review', 'queued', 'processing', 'mapped', 'casted', 'blocked', 'failed'] as $itemStatus)
                        <option value="{{ $itemStatus }}">{{ $itemStatus }}</option>
                    @endforeach
                </flux:select>
                <flux:select label="Điều kiện xử lý" wire:model.live="eligibilityFilter">
                    <option value="">Tất cả URL ({{ number_format($eligibilityCounts['total']) }})</option>
                    <option value="eligible">Phù hợp với {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }} ({{ number_format($eligibilityCounts['eligible']) }})</option>
                    <option value="ineligible">Không phù hợp với {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }} ({{ number_format($eligibilityCounts['ineligible']) }})</option>
                    <option value="different_type">Khác loại dữ liệu ({{ number_format($eligibilityCounts['different_type']) }})</option>
                    <option value="missing_root">Thiếu root object ({{ number_format($eligibilityCounts['missing_root']) }})</option>
                    <option value="partial_root">Root chỉ là partial ({{ number_format($eligibilityCounts['partial_root']) }})</option>
                    <option value="processed">Đã/đang xử lý ({{ number_format($eligibilityCounts['processed']) }})</option>
                    <option value="media_review">Ảnh cần kiểm tra (đã bỏ qua / dùng placeholder)</option>
                    <option value="image_count_limit">Ảnh vượt giới hạn mỗi bài (kể cả cảnh báo cũ)</option>
                </flux:select>
            </div>

            <flux:error name="failureFilter" />
            <flux:error name="eligibilityFilter" />
            <flux:error name="mediaRetry" />

            @if ($mediaRetryMode)
                <flux:callout variant="warning" heading="Tải lại ảnh trên bài đã tạo">
                    <p>{{ number_format($urls->total()) }} URL khớp bộ lọc; {{ number_format($mediaRetryCount) }} URL đã cast có thể đưa vào queue tải lại. Giới hạn hiện tại: {{ max(1, (int) config('legacy_migration.media.max_images_per_object', 100)) }} ảnh/bài.</p>
                    <p>Giữ target đã lưu, JSON nguồn, timestamp nguồn và chế độ URL cũ. Ảnh tốt đã có trong Media được tái dùng; không import lại hoặc tạo blog mới.</p>
                    <p>Content được cast lại từ nguồn với chính sách ghi đè để khôi phục ảnh hoặc thay placeholder. Ảnh URL vẫn không tải được sẽ bị bỏ khỏi content. Nếu bài đã được biên tập thêm sau migrate, cần đối soát trước khi chạy.</p>
                    @if ($mediaRetryUnavailableReason)
                        <p>{{ $mediaRetryUnavailableReason }}</p>
                    @endif
                    <flux:button
                        type="button"
                        wire:click="queueMediaRetry"
                        wire:loading.attr="disabled"
                        wire:target="queueMediaRetry"
                        :disabled="$run->status === 'receiving' || $mediaRetryCount === 0 || $mediaRetryUnavailableReason !== null"
                        wire:confirm="Tải lại ảnh cho {{ number_format($mediaRetryCount) }} URL trên tất cả các trang khớp bộ lọc hiện tại? Giữ target và chế độ URL đã lưu; ghi đè content từ nguồn."
                    >Tải lại ảnh {{ number_format($mediaRetryCount) }} URL theo bộ lọc</flux:button>
                </flux:callout>
            @endif

            @if ($failureFilter !== '')
                <div class="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950/20 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-red-900 dark:text-red-100">Đang lọc nhóm lỗi · {{ number_format($urls->total()) }} URL trong danh sách</p>
                        <p class="mt-1 break-words text-sm text-red-800 dark:text-red-200" title="{{ $failureFilter }}">{{ \Illuminate\Support\Str::limit($failureFilter, 500) }}</p>
                        <p class="mt-1 text-xs text-red-700 dark:text-red-300">Chỉ lọc danh sách; nút xử lý tự động theo loại vẫn áp dụng cho toàn phiên. Có thể chọn checkbox để xử lý đúng các URL cần thiết.</p>
                    </div>
                    <flux:button type="button" size="sm" wire:click="clearFailureFilter" wire:loading.attr="disabled">Bỏ lọc nhóm lỗi</flux:button>
                </div>
            @endif

            @if ($eligibilityFilter !== '')
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Phân loại theo cấu hình tự động <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }}</span>.
                    Bộ lọc loại dữ liệu và trạng thái đã được đặt về Tất cả để số URL khớp đúng với thống kê của phiên.
                </p>
            @endif

            @if ($selectedCount > 0)
                <div class="flex flex-col gap-3 rounded-2xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-900 dark:bg-violet-950/30 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-semibold text-violet-900 dark:text-violet-100">Đã chọn {{ number_format($selectedCount) }} URL</p>
                        <p class="text-xs text-violet-700 dark:text-violet-300">{{ $mediaRetryMode ? 'Tải lại ảnh bằng target đã lưu và ghi đè content từ nguồn.' : 'Các URL phải cùng loại dữ liệu và có root object đầy đủ.' }}</p>
                        <flux:error name="bulkSelection" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" variant="ghost" wire:click="clearSelection">Bỏ chọn</flux:button>
                        @if ($mediaRetryMode)
                            <flux:button type="button" variant="primary" color="violet"
                                wire:click="queueMediaRetry(true)" wire:loading.attr="disabled" wire:target="queueMediaRetry"
                                :disabled="$run->status === 'receiving' || $mediaRetryUnavailableReason !== null"
                                wire:confirm="Tải lại ảnh đúng {{ number_format($selectedCount) }} URL đã chọn? Giữ target và chế độ URL cũ; ghi đè content từ nguồn."
                            >Tải lại ảnh {{ number_format($selectedCount) }} URL đã chọn</flux:button>
                        @else
                            <flux:button type="button" variant="primary" color="violet" wire:click="openBulkProcessing" wire:loading.attr="disabled" wire:target="openBulkProcessing">
                                Xử lý {{ number_format($selectedCount) }} URL đã chọn
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $pageSelectableIds = $mediaRetryMode ? $pageMediaRetryIds : collect($urls->items())
                    ->filter(fn ($url) => in_array($url->status, $processableStatuses, true)
                        && isset($automaticSourceTypes[$url->root_object_type])
                        && ! (bool) $url->root_is_partial)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $allPageSelected = $pageSelectableIds !== []
                    && collect($pageSelectableIds)->every(fn ($id) => $this->isUrlSelected($id));
            @endphp
            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <flux:checkbox
                                    aria-label="Chọn các URL có thể xử lý trên trang này"
                                    :checked="$allPageSelected"
                                    :disabled="$pageSelectableIds === []"
                                    wire:click="togglePageSelection({{ json_encode($pageSelectableIds) }})"
                                />
                            </th>
                            <th class="px-4 py-3">URL nguồn</th><th class="px-4 py-3">Loại / root</th><th class="px-4 py-3">Traffic</th><th class="px-4 py-3">Đích</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($urls as $url)
                            @php
                                $eligibilityReason = match (true) {
                                    $url->status === 'queued' => ['label' => 'Đang chờ worker', 'color' => 'amber'],
                                    $url->status === 'processing' => ['label' => 'Đang xử lý', 'color' => 'amber'],
                                    ! in_array($url->status, $processableStatuses, true) => ['label' => 'Đã/đang xử lý', 'color' => 'zinc'],
                                    ! $url->root_object_type => ['label' => 'Thiếu root object', 'color' => 'red'],
                                    $url->root_object_type !== $automaticSourceType => ['label' => 'Khác loại dữ liệu', 'color' => 'amber'],
                                    (bool) $url->root_is_partial => ['label' => 'Root chỉ là partial', 'color' => 'amber'],
                                    default => ['label' => 'Phù hợp tự động', 'color' => 'green'],
                                };
                            @endphp
                            <tr wire:key="legacy-url-{{ $url->id }}" class="{{ $selectedUrl?->id === $url->id ? 'bg-teal-50/60 dark:bg-teal-950/20' : '' }}">
                                <td class="px-4 py-3">
                                    @if (in_array($url->id, $pageSelectableIds, true))
                                        <flux:checkbox
                                            aria-label="Chọn URL {{ $url->normalized_path }}"
                                            :checked="$this->isUrlSelected($url->id)"
                                            wire:click="toggleUrlSelection({{ $url->id }})"
                                        />
                                    @else
                                        <span class="inline-block h-5 w-5" title="URL chưa đủ điều kiện xử lý hàng loạt"></span>
                                    @endif
                                </td>
                                <td class="max-w-md px-4 py-3"><p class="break-all font-medium text-zinc-900 dark:text-white">{{ $url->normalized_path }}</p><p class="mt-1 text-xs text-zinc-500">{{ $url->raw_url }}</p></td>
                                <td class="px-4 py-3">
                                    <p>{{ $url->route_kind }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $url->root_object_key ?: 'Không có root' }}</p>
                                    <p class="mt-1 text-xs text-zinc-400">{{ $automaticSourceTypes[$url->root_object_type] ?? ($url->root_object_type ?: 'Chưa nhận diện') }}</p>
                                    <flux:badge class="mt-2" size="sm" :color="$eligibilityReason['color']">{{ $eligibilityReason['label'] }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format($url->clicks) }} clicks</td>
                                <td class="max-w-sm px-4 py-3"><p>{{ $url->target_type ?: 'Chưa chọn' }}</p><p class="mt-1 break-all text-xs text-zinc-500">{{ $url->target_path }}</p></td>
                                <td class="min-w-56 px-4 py-3">
                                    <flux:badge>{{ $url->status }}</flux:badge>
                                    @if ($url->latestMediaAudit?->status === 'warning')
                                        <flux:badge color="amber">{{ number_format((int) data_get($url->latestMediaAudit->after_json, 'skipped', 0)) }} ảnh cần kiểm tra</flux:badge>
                                    @endif
                                    @if ($url->error_text)
                                        <p class="mt-2 break-words text-xs text-red-600 dark:text-red-300" title="{{ $url->error_text }}">{{ \Illuminate\Support\Str::limit((string) $url->error_text, 180) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <flux:button type="button" size="sm" wire:click="selectUrl({{ $url->id }})">Chọn</flux:button>
                                        @if (in_array($url->status, ['queued', 'processing'], true))
                                            <flux:button
                                                type="button"
                                                size="sm"
                                                wire:click="recoverStuckUrl({{ $url->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="recoverStuckUrls,recoverStuckUrl"
                                                :disabled="$run->status === 'receiving' || $queueRecovery['unavailable_reason'] !== null || ! $url->updated_at || $url->updated_at->gt(now()->subSeconds($queueRecovery['stale_seconds']))"
                                                wire:confirm="Khôi phục URL này bằng target cũ? Nếu job còn trong queue hoặc đang chạy, hệ thống sẽ không tạo job trùng."
                                            >Khôi phục và chạy lại</flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">Không có URL khớp bộ lọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $urls->links() }}
        </section>

        <flux:modal name="legacy-bulk-processing" wire:model="bulkProcessingModalOpen" focusable class="max-w-4xl">
            <form wire:submit="queueSelectedProcessing" class="space-y-5">
                <div>
                    <flux:heading size="lg">Xử lý {{ number_format($selectedCount) }} URL đã chọn</flux:heading>
                    <flux:subheading>
                        Loại dữ liệu: {{ $automaticSourceTypes[$automaticSourceType] ?? $automaticSourceType }}. Hệ thống chỉ tạo queue cho đúng các URL đã chọn và sẽ kiểm tra lại trước khi ghi.
                    </flux:subheading>
                </div>

                @error('bulkSelection')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $message }}</div>
                @enderror

                <div class="grid gap-3 md:grid-cols-2">
                    <flux:select label="Cách tìm / tạo đích" wire:model.live="automaticStrategy">
                        @foreach ($automaticStrategies as $strategy => $label)
                            <option wire:key="legacy-bulk-strategy-{{ $strategy }}" value="{{ $strategy }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Sau khi cast" wire:model="automaticMappingMode">
                        <option value="cast_and_redirect">Cast + redirect 301 (khuyến nghị)</option>
                        <option value="cast_preserve_url">Cast + giữ URL cũ trả 200</option>
                        <option value="cast_only">Chỉ cast, chưa redirect</option>
                    </flux:select>
                    <flux:select label="Chính sách field" wire:model="automaticMergePolicy">
                        <option value="overwrite">Ghi dữ liệu nguồn vào field whitelist</option>
                        <option value="fill_blanks">Chỉ điền field đang trống</option>
                    </flux:select>
                    <div class="pt-7">
                        <flux:checkbox wire:model="automaticImportMedia" label="Tải ảnh về Media Library" :disabled="$automaticSourceType !== 'blog'" />
                        <flux:error name="automaticImportMedia" />
                    </div>
                </div>

                <flux:callout variant="info" heading="An toàn URL và thời gian">
                    Chế độ mặc định tạo/ghép đúng đối tượng, redirect URL nguồn 301 về URL CMS và áp dụng <code>created_at</code>, <code>updated_at</code> từ dữ liệu cũ. URL CMS canonical về chính nó; blog tải ảnh về Media Library khi tùy chọn ảnh được bật.
                </flux:callout>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="filled">Đóng</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="submit"
                        variant="primary"
                        color="violet"
                        wire:loading.attr="disabled"
                        wire:target="queueSelectedProcessing"
                        :disabled="$selectedCount === 0 || $run->status === 'receiving'"
                        wire:confirm="Đưa đúng {{ number_format($selectedCount) }} URL đã chọn vào queue?"
                    >
                        <span wire:loading.remove wire:target="queueSelectedProcessing">Xác nhận xử lý {{ number_format($selectedCount) }} URL</span>
                        <span wire:loading wire:target="queueSelectedProcessing">Đang tạo hàng đợi...</span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
