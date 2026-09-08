<div class="fixed inset-0 z-[120] hidden px-4 py-6 sm:px-6 lg:px-8" data-frontsite-gallery-lightbox aria-hidden="true" role="dialog" aria-modal="true" aria-label="Trình xem media">
    <button type="button" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" data-gallery-backdrop aria-label="Đóng gallery lightbox"></button>

    <div class="relative mx-auto flex h-full max-w-6xl items-center">
        <div class="relative w-full overflow-hidden rounded-[2rem] border border-white/10 bg-[#071120] shadow-[0_40px_120px_-40px_rgba(15,23,42,0.88)]">
            <div class="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 text-white sm:px-6">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-100" data-gallery-badge>
                        <i class="fa-regular fa-image"></i>
                        Ảnh
                    </span>
                    <h2 class="frontsite-h2-card frontsite-h2-inverse mt-3" data-gallery-title></h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-200/82" data-gallery-description></p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex min-w-[4.75rem] items-center justify-center rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-100" data-gallery-counter>
                        1 / 1
                    </span>

                    <button type="button" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white transition hover:bg-white/14" data-gallery-close aria-label="Đóng gallery">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="bg-slate-950/90 p-3 sm:p-6">
                <div class="relative overflow-hidden rounded-[1.5rem] bg-black/35">
                    <button
                        type="button"
                        class="absolute left-3 top-1/2 z-10 inline-flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/16 bg-slate-950/44 text-white backdrop-blur-sm transition hover:bg-white/14 disabled:cursor-not-allowed disabled:opacity-35"
                        data-gallery-prev
                        aria-label="Xem media trước"
                    >
                        <i class="fa-solid fa-chevron-left text-sm"></i>
                    </button>

                    <button
                        type="button"
                        class="absolute right-3 top-1/2 z-10 inline-flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/16 bg-slate-950/44 text-white backdrop-blur-sm transition hover:bg-white/14 disabled:cursor-not-allowed disabled:opacity-35"
                        data-gallery-next
                        aria-label="Xem media tiếp theo"
                    >
                        <i class="fa-solid fa-chevron-right text-sm"></i>
                    </button>

                    <img src="" alt="" class="hidden max-h-[72vh] w-full object-contain" data-gallery-image>
                    <video controls playsinline class="hidden max-h-[72vh] w-full bg-black" data-gallery-video></video>
                    <iframe
                        class="hidden aspect-video w-full bg-black"
                        data-gallery-iframe
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
