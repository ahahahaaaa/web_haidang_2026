const activeThumbClasses = [
    'border-primary',
    'ring-2',
    'ring-primary/15',
    'shadow-[0_24px_50px_-36px_rgba(255,106,0,0.55)]',
];

const inactiveThumbClasses = [
    'border-slate-200',
    'hover:border-orange-200',
];

function normalizeIndex(index, total) {
    if (total < 1) {
        return 0;
    }

    return (index + total) % total;
}

function resolveGalleryData(thumb) {
    return {
        alt: thumb.dataset.tourGalleryAlt || thumb.dataset.tourGalleryTitle || 'Ảnh gallery tour',
        description: thumb.dataset.tourGalleryDescription || '',
        embed: thumb.dataset.tourGalleryEmbed || '',
        icon: thumb.dataset.tourGalleryIcon || 'fa-regular fa-image',
        kind: thumb.dataset.tourGalleryKind || 'image',
        label: thumb.dataset.tourGalleryLabel || 'Ảnh',
        poster: thumb.dataset.tourGalleryPoster || '',
        src: thumb.dataset.tourGallerySrc || '',
        title: thumb.dataset.tourGalleryTitle || '',
    };
}

export function initTourGallerySliders() {
    document.querySelectorAll('[data-tour-gallery]').forEach((gallery) => {
        if (gallery.dataset.tourGalleryReady === 'true') {
            return;
        }

        gallery.dataset.tourGalleryReady = 'true';

        const thumbs = Array.from(gallery.querySelectorAll('[data-tour-gallery-thumb]'));
        const stage = gallery.querySelector('[data-tour-gallery-stage]');
        const image = gallery.querySelector('[data-tour-gallery-image]');
        const video = gallery.querySelector('[data-tour-gallery-video]');
        const iframe = gallery.querySelector('[data-tour-gallery-iframe]');
        const title = gallery.querySelector('[data-tour-gallery-active-title]');
        const description = gallery.querySelector('[data-tour-gallery-active-description]');
        const badge = gallery.querySelector('[data-tour-gallery-badge]');
        const counter = gallery.querySelector('[data-tour-gallery-counter]');
        const prev = gallery.querySelector('[data-tour-gallery-prev]');
        const next = gallery.querySelector('[data-tour-gallery-next]');
        const openActive = gallery.querySelector('[data-tour-gallery-open-active]');
        const thumbRail = gallery.querySelector('[data-tour-gallery-thumb-rail]');
        const thumbRailLayout = thumbRail?.dataset.tourGalleryThumbLayout || 'horizontal';
        const autoplayEnabled = gallery.dataset.tourGalleryAutoplay === 'true';
        const autoplayDelay = Math.max(2500, Number.parseInt(gallery.dataset.tourGalleryAutoplayDelay || '4600', 10) || 4600);

        if (! stage || ! image || ! video || ! iframe || !thumbs.length) {
            return;
        }

        let activeIndex = Math.max(0, thumbs.findIndex((thumb) => thumb.getAttribute('aria-pressed') === 'true'));
        let autoplayTimer = null;
        let autoplayPaused = false;
        let touchStartX = null;

        const isDesktopVerticalThumbRail = () => (
            thumbRailLayout === 'vertical-desktop'
            && window.matchMedia('(min-width: 1024px)').matches
        );

        const pauseStageVideo = () => {
            video.pause();
            video.removeAttribute('src');
            video.removeAttribute('poster');
            video.load();
        };

        const resetStage = () => {
            image.classList.add('hidden');
            video.classList.add('hidden');
            iframe.classList.add('hidden');

            image.removeAttribute('src');
            image.removeAttribute('alt');
            pauseStageVideo();
            iframe.setAttribute('src', '');
        };

        const clearAutoplay = () => {
            if (autoplayTimer !== null) {
                window.clearTimeout(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const scheduleAutoplay = () => {
            clearAutoplay();

            if (! autoplayEnabled || autoplayPaused || thumbs.length <= 1) {
                return;
            }

            autoplayTimer = window.setTimeout(() => {
                step(1);
            }, autoplayDelay);
        };

        const pauseAutoplay = () => {
            autoplayPaused = true;
            clearAutoplay();
        };

        const resumeAutoplay = () => {
            if (! autoplayEnabled || thumbs.length <= 1) {
                return;
            }

            autoplayPaused = false;
            scheduleAutoplay();
        };

        const syncNavigationState = () => {
            const canNavigate = thumbs.length > 1;

            if (counter) {
                counter.textContent = `${activeIndex + 1} / ${thumbs.length}`;
            }

            if (prev) {
                prev.disabled = ! canNavigate;
            }

            if (next) {
                next.disabled = ! canNavigate;
            }
        };

        const keepActiveThumbVisible = (thumb) => {
            if (! thumbRail || ! thumb) {
                return;
            }

            if (isDesktopVerticalThumbRail()) {
                const thumbTop = thumb.offsetTop;
                const thumbBottom = thumbTop + thumb.offsetHeight;
                const railTop = thumbRail.scrollTop;
                const railBottom = railTop + thumbRail.clientHeight;

                if (thumbTop >= railTop && thumbBottom <= railBottom) {
                    return;
                }

                const nextTop = thumbTop - Math.max((thumbRail.clientHeight - thumb.offsetHeight) / 2, 0);

                if (typeof thumbRail.scrollTo === 'function') {
                    thumbRail.scrollTo({
                        top: Math.max(0, nextTop),
                        behavior: 'smooth',
                    });
                } else {
                    thumbRail.scrollTop = Math.max(0, nextTop);
                }

                return;
            }

            const thumbLeft = thumb.offsetLeft;
            const thumbRight = thumbLeft + thumb.offsetWidth;
            const railLeft = thumbRail.scrollLeft;
            const railRight = railLeft + thumbRail.clientWidth;

            if (thumbLeft >= railLeft && thumbRight <= railRight) {
                return;
            }

            const nextLeft = thumbLeft - Math.max((thumbRail.clientWidth - thumb.offsetWidth) / 2, 0);

            if (typeof thumbRail.scrollTo === 'function') {
                thumbRail.scrollTo({
                    left: Math.max(0, nextLeft),
                    behavior: 'smooth',
                });
            } else {
                thumbRail.scrollLeft = Math.max(0, nextLeft);
            }
        };

        const syncThumbState = (keepThumbVisible = true) => {
            thumbs.forEach((thumb, index) => {
                const isActive = index === activeIndex;

                thumb.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                activeThumbClasses.forEach((className) => thumb.classList.toggle(className, isActive));
                inactiveThumbClasses.forEach((className) => thumb.classList.toggle(className, ! isActive));

                if (isActive && keepThumbVisible && thumbs.length > 1) {
                    keepActiveThumbVisible(thumb);
                }
            });
        };

        const renderStage = (index, scrollIntoView = true) => {
            activeIndex = normalizeIndex(index, thumbs.length);

            const activeThumb = thumbs[activeIndex];
            const data = resolveGalleryData(activeThumb);

            resetStage();

            if (badge) {
                badge.innerHTML = `<i class="${data.icon}"></i>${data.label}`;
            }

            if (title) {
                title.textContent = data.title;
            }

            if (description) {
                description.textContent = data.description;
            }

            if (openActive) {
                openActive.dataset.activeIndex = `${activeIndex}`;
            }

            if (data.kind === 'youtube' && data.embed) {
                iframe.classList.remove('hidden');
                iframe.setAttribute('src', data.embed);
            } else if (data.kind === 'mp4' && data.src) {
                video.classList.remove('hidden');
                video.setAttribute('src', data.src);

                if (data.poster) {
                    video.setAttribute('poster', data.poster);
                }

                video.load();
            } else if (data.src) {
                image.classList.remove('hidden');
                image.setAttribute('src', data.src);
                image.setAttribute('alt', data.alt);
            }

            syncNavigationState();
            syncThumbState(scrollIntoView);
            scheduleAutoplay();
        };

        const step = (direction) => {
            if (thumbs.length <= 1) {
                return;
            }

            renderStage(activeIndex + direction);
        };

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => {
                renderStage(index, false);
            });
        });

        prev?.addEventListener('click', () => {
            step(-1);
        });

        next?.addEventListener('click', () => {
            step(1);
        });

        openActive?.addEventListener('click', () => {
            const trigger = gallery.querySelector(`[data-tour-gallery-lightbox-trigger][data-tour-gallery-index="${activeIndex}"]`);

            if (trigger instanceof HTMLElement) {
                trigger.click();
            }
        });

        stage.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0]?.clientX ?? null;
        }, { passive: true });

        stage.addEventListener('touchend', (event) => {
            const touchEndX = event.changedTouches[0]?.clientX ?? null;

            if (touchStartX === null || touchEndX === null) {
                touchStartX = null;

                return;
            }

            const delta = touchEndX - touchStartX;
            touchStartX = null;

            if (Math.abs(delta) < 36) {
                return;
            }

            step(delta > 0 ? -1 : 1);
        }, { passive: true });

        gallery.addEventListener('keydown', (event) => {
            if (event.target instanceof HTMLElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                step(-1);

                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                step(1);
            }
        });

        gallery.addEventListener('mouseenter', pauseAutoplay);
        gallery.addEventListener('mouseleave', resumeAutoplay);
        gallery.addEventListener('focusin', pauseAutoplay);
        gallery.addEventListener('focusout', (event) => {
            if (event.relatedTarget instanceof Node && gallery.contains(event.relatedTarget)) {
                return;
            }

            resumeAutoplay();
        });

        renderStage(activeIndex, false);
    });
}
