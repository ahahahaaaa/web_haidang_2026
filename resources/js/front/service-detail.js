const carouselControllers = new WeakMap();
const revealPresets = Object.freeze({
    eyebrow: { effect: 'animate__fadeInDown', duration: '560ms', delay: 0 },
    meta: { effect: 'animate__fadeIn', duration: '520ms', delay: 0 },
    title: { effect: 'animate__fadeInUp', duration: '720ms', delay: 0.08 },
    body: { effect: 'animate__fadeInUp', duration: '680ms', delay: 0.14 },
    cta: { effect: 'animate__fadeInUp', duration: '640ms', delay: 0.2 },
    stat: { effect: 'animate__fadeInUp', duration: '580ms', delay: 0.08 },
    card: { effect: 'animate__fadeInUp', duration: '620ms', delay: 0.1 },
    copy: { effect: 'animate__fadeInUp', duration: '700ms', delay: 0.12 },
    panel: { effect: 'animate__fadeInUp', duration: '640ms', delay: 0.12 },
});
const revealEffectClasses = [...new Set(Object.values(revealPresets).map((preset) => preset.effect))];
const revealedElements = new WeakSet();
const tourDetailExpandableControllers = new WeakMap();
const socialShareBlocks = new WeakSet();
const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)') ?? { matches: false };
const heroFocusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');
let galleryLightboxInitialized = false;
let activeGalleryLightboxTriggers = [];
let activeGalleryLightboxIndex = -1;
let activeGalleryLightboxFocusTarget = null;
let textRevealObserver = null;
let interactionRefreshBound = false;

function resetRevealState(element) {
    const appliedEffect = element.dataset.appliedRevealEffect;

    element.classList.remove('animate__animated', 'is-revealed');
    revealEffectClasses.forEach((className) => element.classList.remove(className));
    if (appliedEffect) {
        element.classList.remove(appliedEffect);
        delete element.dataset.appliedRevealEffect;
    }
    element.style.removeProperty('--animate-duration');
    element.style.removeProperty('animationDelay');
}

function getRevealPreset(kind) {
    return revealPresets[kind] ?? revealPresets.body;
}

function getRevealDelay(element, fallbackDelay = 0) {
    const parsedDelay = Number.parseFloat(element.dataset.revealDelay ?? '');

    return Number.isFinite(parsedDelay) ? parsedDelay : fallbackDelay;
}

function animateRevealElement(element, kind, fallbackDelay = 0) {
    const preset = getRevealPreset(kind);
    const customEffect = element.dataset.heroEffect || element.dataset.revealEffect || '';
    const resolvedEffect = customEffect || preset.effect;

    resetRevealState(element);
    element.classList.add('is-revealed');

    if (prefersReducedMotion.matches) {
        return;
    }

    const delay = getRevealDelay(element, preset.delay + fallbackDelay);

    element.style.setProperty('--animate-duration', preset.duration);
    element.style.animationDelay = `${delay}s`;
    element.classList.add('animate__animated', resolvedEffect);
    element.dataset.appliedRevealEffect = resolvedEffect;
}

function initTextReveals() {
    document.documentElement.classList.add('js-frontsite-motion');

    const elements = Array.from(document.querySelectorAll('[data-reveal]')).filter((element) => ! element.closest('[data-hero-slide-item]'));

    if (! elements.length) {
        return;
    }

    if (prefersReducedMotion.matches || typeof IntersectionObserver === 'undefined') {
        elements.forEach((element) => {
            animateRevealElement(element, element.dataset.reveal || 'body');
            revealedElements.add(element);
        });

        return;
    }

    if (! textRevealObserver) {
        textRevealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                const element = entry.target;

                animateRevealElement(element, element.dataset.reveal || 'body');
                revealedElements.add(element);
                textRevealObserver?.unobserve(element);
            });
        }, {
            rootMargin: '0px 0px -12% 0px',
            threshold: 0,
        });
    }

    elements.forEach((element) => {
        if (revealedElements.has(element)) {
            return;
        }

        textRevealObserver.observe(element);
    });
}

function resetHeroTextReveal(slide) {
    slide.querySelectorAll('[data-hero-text]').forEach((element) => {
        resetRevealState(element);
    });
}

function animateHeroTextReveal(slide) {
    slide.querySelectorAll('[data-hero-text]').forEach((element, index) => {
        animateRevealElement(element, element.dataset.heroText || 'body', index * 0.08);
    });
}

function setHeroSlideInteractiveState(slide, isActive) {
    const tabindexCacheKey = 'heroSlideTabindex';

    slide.classList.toggle('is-active', isActive);
    slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    slide.toggleAttribute('inert', !isActive);
    syncHeroSlideMediaPlayback(slide, isActive);

    slide.querySelectorAll(heroFocusableSelector).forEach((element) => {
        if (! (element instanceof HTMLElement)) {
            return;
        }

        const hasStoredTabindex = Object.prototype.hasOwnProperty.call(element.dataset, tabindexCacheKey);

        if (isActive) {
            if (! hasStoredTabindex) {
                return;
            }

            if (element.dataset.heroSlideTabindex === '__missing__') {
                element.removeAttribute('tabindex');
            } else {
                element.setAttribute('tabindex', element.dataset.heroSlideTabindex || '0');
            }

            delete element.dataset.heroSlideTabindex;

            return;
        }

        if (! hasStoredTabindex) {
            element.dataset.heroSlideTabindex = element.hasAttribute('tabindex')
                ? (element.getAttribute('tabindex') ?? '0')
                : '__missing__';
        }

        element.setAttribute('tabindex', '-1');
    });
}

function syncHeroSlideMediaPlayback(slide, isActive) {
    slide.querySelectorAll('[data-hero-video]').forEach((video) => {
        if (! (video instanceof HTMLVideoElement)) {
            return;
        }

        if (! isActive) {
            video.pause();

            return;
        }

        if (video.src) {
            video.play().catch(() => {});
        }
    });

    slide.querySelectorAll('[data-hero-iframe]').forEach((iframe) => {
        if (! (iframe instanceof HTMLIFrameElement)) {
            return;
        }

        const source = iframe.dataset.heroIframeSrc?.trim() || iframe.src;

        if (! source) {
            return;
        }

        if (isActive) {
            if (! iframe.src) {
                iframe.src = source;
            }

            return;
        }

        iframe.dataset.heroIframeSrc = source;
        iframe.removeAttribute('src');
    });
}

function initCardCarousels() {
    document.querySelectorAll('[data-card-carousel]').forEach((carousel) => {
        const existingController = carouselControllers.get(carousel);

        if (existingController) {
            existingController.sync();

            return;
        }

        const track = carousel.querySelector('[data-card-carousel-track]');
        const prev = carousel.querySelector('[data-card-carousel-prev]');
        const next = carousel.querySelector('[data-card-carousel-next]');

        if (! track) {
            return;
        }

        const isDesktopSlider = () => carousel.dataset.desktopSlider === 'true';
        const isAutoplayEnabled = () => carousel.dataset.autoplay === 'true' && ! prefersReducedMotion.matches;

        const isActive = () => window.innerWidth < 768 || isDesktopSlider();
        const hasOverflow = () => track.scrollWidth - track.clientWidth > 8;
        const maxScrollLeft = () => Math.max(0, track.scrollWidth - track.clientWidth - 1);
        let syncFrame = null;

        const stepSize = () => {
            const item = track.querySelector('[data-card-carousel-item]');

            return item ? item.offsetWidth + 16 : Math.max(track.clientWidth * 0.9, 240);
        };
        const interval = Math.max(Number.parseInt(carousel.dataset.interval ?? '', 10) || 0, 3200);
        let autoplayTimer;

        const syncButtons = () => {
            const active = isActive();
            const overflow = hasOverflow();
            const maxScroll = maxScrollLeft();

            carousel.dataset.active = active ? 'true' : 'false';

            if (prev) {
                prev.disabled = ! active || ! overflow || track.scrollLeft <= 4;
            }

            if (next) {
                next.disabled = ! active || ! overflow || track.scrollLeft >= maxScroll;
            }

            if (! active) {
                track.scrollLeft = 0;
            }
        };
        const scheduleSync = () => {
            if (syncFrame !== null) {
                return;
            }

            syncFrame = window.requestAnimationFrame(() => {
                syncFrame = null;
                syncButtons();
            });
        };

        const clearAutoplay = () => {
            if (! autoplayTimer) {
                return;
            }

            window.clearInterval(autoplayTimer);
            autoplayTimer = undefined;
        };

        const scrollTrackTo = (position) => {
            const nextPosition = Math.max(0, position);

            if (typeof track.scrollTo === 'function') {
                track.scrollTo({ left: nextPosition, behavior: 'smooth' });
            } else {
                track.scrollLeft = nextPosition;
            }

            scheduleSync();
        };

        const scrollTrack = (distance) => {
            if (typeof track.scrollBy === 'function') {
                track.scrollBy({ left: distance, behavior: 'smooth' });
            } else {
                track.scrollLeft += distance;
            }

            scheduleSync();
        };

        const advanceTrack = () => {
            const maxScroll = maxScrollLeft();

            if (!isActive() || !hasOverflow() || maxScroll <= 8) {
                return;
            }

            const nextPosition = track.scrollLeft + stepSize();

            if (nextPosition >= maxScroll - 4) {
                scrollTrackTo(0);

                return;
            }

            scrollTrack(stepSize());
        };

        const restartAutoplay = () => {
            clearAutoplay();

            if (! isAutoplayEnabled() || ! isActive() || ! hasOverflow()) {
                return;
            }

            autoplayTimer = window.setInterval(advanceTrack, interval);
        };

        prev?.addEventListener('click', () => {
            scrollTrack(-stepSize());
            restartAutoplay();
        });

        next?.addEventListener('click', () => {
            scrollTrack(stepSize());
            restartAutoplay();
        });

        track.addEventListener('scroll', () => {
            scheduleSync();
        }, { passive: true });

        carousel.addEventListener('mouseenter', clearAutoplay);
        carousel.addEventListener('mouseleave', restartAutoplay);
        carousel.addEventListener('focusin', clearAutoplay);
        carousel.addEventListener('focusout', (event) => {
            if (! carousel.contains(event.relatedTarget)) {
                restartAutoplay();
            }
        });
        track.addEventListener('pointerdown', clearAutoplay, { passive: true });
        track.addEventListener('pointerup', restartAutoplay, { passive: true });
        track.addEventListener('touchstart', clearAutoplay, { passive: true });
        track.addEventListener('touchend', restartAutoplay, { passive: true });

        window.addEventListener('resize', () => {
            syncButtons();
            restartAutoplay();
        });

        track.querySelectorAll('img').forEach((image) => {
            if (! image.complete) {
                image.addEventListener('load', () => {
                    scheduleSync();
                    restartAutoplay();
                }, { once: true });
                image.addEventListener('error', () => {
                    scheduleSync();
                    restartAutoplay();
                }, { once: true });
            }
        });

        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(() => {
                scheduleSync();
                restartAutoplay();
            });

            observer.observe(track);
            carouselControllers.set(carousel, {
                observer,
                sync: () => {
                    scheduleSync();
                    restartAutoplay();
                },
            });
        } else {
            carouselControllers.set(carousel, {
                sync: () => {
                    scheduleSync();
                    restartAutoplay();
                },
            });
        }

        scheduleSync();
        restartAutoplay();
    });
}

function initTourItineraryDetails() {
    document.querySelectorAll('[data-tour-itinerary-details]').forEach((details) => {
        if (details.dataset.itineraryInitialized === 'true') {
            return;
        }

        const icon = details.querySelector('[data-tour-itinerary-icon]');

        if (! icon) {
            return;
        }

        details.dataset.itineraryInitialized = 'true';

        const syncIcon = () => {
            icon.classList.toggle('fa-minus', details.open);
            icon.classList.toggle('fa-plus', ! details.open);
        };

        syncIcon();
        details.addEventListener('toggle', syncIcon);
    });
}
function initFaqAccordions() {
    document.querySelectorAll('[data-faq-accordion]').forEach((accordion) => {
        if (accordion.dataset.faqInitialized === 'true') {
            return;
        }

        accordion.dataset.faqInitialized = 'true';

        const items = Array.from(accordion.querySelectorAll('[data-faq-item]'));
        const singleMode = accordion.dataset.faqSingle !== 'false';

        const syncItem = (item, isOpen) => {
            const trigger = item.querySelector('[data-faq-trigger]');
            const panel = item.querySelector('[data-faq-panel]');
            const icon = item.querySelector('[data-faq-icon]');

            if (! trigger || ! panel || ! icon) {
                return;
            }

            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            panel.hidden = ! isOpen;
            item.classList.toggle('ring-1', isOpen);
            item.classList.toggle('is-faq-open', isOpen);
            icon.classList.toggle('fa-plus', ! isOpen);
            icon.classList.toggle('fa-minus', isOpen);
        };
        const scrollItineraryItemIntoView = (trigger, item) => {
            if (! trigger.id?.startsWith('tour-itinerary-trigger-')) {
                return;
            }

            window.requestAnimationFrame(() => {
                const headerHeight = document.querySelector('body > header')?.getBoundingClientRect().height ?? 80;
                const top = item.getBoundingClientRect().top + window.scrollY - headerHeight - 16;

                window.scrollTo({
                    top: Math.max(0, top),
                    behavior: prefersReducedMotion.matches ? 'auto' : 'smooth',
                });
            });
        };

        let hasOpenItem = false;

        items.forEach((item, index) => {
            const trigger = item.querySelector('[data-faq-trigger]');

            if (! trigger) {
                return;
            }

            const isInitiallyOpen = trigger.getAttribute('aria-expanded') === 'true';

            if (isInitiallyOpen && ! hasOpenItem) {
                hasOpenItem = true;
                syncItem(item, true);
            } else {
                syncItem(item, false);
            }

            trigger.addEventListener('click', () => {
                const willOpen = trigger.getAttribute('aria-expanded') !== 'true';

                if (singleMode && willOpen) {
                    items.forEach((otherItem) => {
                        if (otherItem !== item) {
                            syncItem(otherItem, false);
                        }
                    });
                }

                syncItem(item, willOpen);

                if (willOpen) {
                    scrollItineraryItemIntoView(trigger, item);
                }
            });

            if (! hasOpenItem && index === items.length - 1 && items[0]) {
                syncItem(items[0], true);
            }
        });
    });
}

function initConsultationModal() {
    const modal = document.querySelector('[data-frontsite-consultation-modal], [data-service-consultation-modal]');

    if (! modal) {
        return;
    }

    const titleTarget = modal.querySelector('[data-consultation-title]');
    const descriptionTarget = modal.querySelector('[data-consultation-description]');
    const contextInput = modal.querySelector('[data-consultation-context-input]');
    const pageUrlInput = modal.querySelector('[data-consultation-page-url-input]');
    const defaultTitle = modal.dataset.defaultTitle || titleTarget?.textContent || '';
    const defaultDescription = modal.dataset.defaultDescription || descriptionTarget?.textContent || '';
    const defaultContext = contextInput?.dataset.defaultValue || contextInput?.value || '';

    const syncModalContent = (trigger = null) => {
        const resolvedTitle = trigger?.dataset.consultationTitle || defaultTitle;
        const resolvedDescription = trigger?.dataset.consultationDescription || defaultDescription;
        const resolvedContext = trigger?.dataset.consultationContext || defaultContext;

        if (titleTarget) {
            titleTarget.textContent = resolvedTitle;
        }

        if (descriptionTarget) {
            descriptionTarget.textContent = resolvedDescription;
        }

        if (contextInput) {
            contextInput.value = resolvedContext;
        }

        if (pageUrlInput) {
            pageUrlInput.value = window.location.href;
        }
    };

    const open = () => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('service-modal-open');
    };

    const close = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('service-modal-open');
    };

    document.querySelectorAll('[data-consultation-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (button.tagName === 'A') {
                event.preventDefault();
            }

            syncModalContent(button);
            open();
        });
    });

    modal.querySelectorAll('[data-consultation-close], [data-consultation-backdrop]').forEach((button) => {
        button.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            close();
        }
    });

    if (modal.dataset.openOnLoad === 'true') {
        syncModalContent();
        open();
    }
}

function copyTextFallback(text) {
    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';

    document.body.append(textarea);
    textarea.focus();
    textarea.select();

    try {
        return document.execCommand('copy');
    } finally {
        textarea.remove();
    }
}

function writeTextToClipboard(text) {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }

    return new Promise((resolve, reject) => {
        if (copyTextFallback(text)) {
            resolve();

            return;
        }

        reject(new Error('Copy failed'));
    });
}

function initSocialShareBlocks() {
    document.querySelectorAll('[data-social-share]').forEach((block) => {
        if (socialShareBlocks.has(block)) {
            return;
        }

        socialShareBlocks.add(block);

        const shareUrl = block.dataset.shareUrl || window.location.href;
        const shareTitle = block.dataset.shareTitle || document.title;
        const shareText = block.dataset.shareText || '';
        const status = block.querySelector('[data-social-share-status]');

        const setStatus = (message) => {
            if (status) {
                status.textContent = message;
            }
        };

        block.querySelectorAll('[data-social-share-copy]').forEach((button) => {
            const label = button.querySelector('[data-social-share-copy-label]');
            const defaultLabel = label?.textContent || 'Sao chép link';
            let resetTimer;

            button.addEventListener('click', async () => {
                try {
                    await writeTextToClipboard(shareUrl);
                    setStatus('Đã sao chép liên kết.');

                    if (label) {
                        label.textContent = 'Đã sao chép';
                        window.clearTimeout(resetTimer);
                        resetTimer = window.setTimeout(() => {
                            label.textContent = defaultLabel;
                        }, 2200);
                    }
                } catch (error) {
                    setStatus('Chưa thể sao chép tự động. Vui lòng sao chép liên kết trên thanh địa chỉ.');
                }
            });
        });

        block.querySelectorAll('[data-social-share-native]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (navigator.share) {
                    try {
                        await navigator.share({
                            title: shareTitle,
                            text: shareText || shareTitle,
                            url: shareUrl,
                        });
                        setStatus('Đã mở khung chia sẻ.');

                        return;
                    } catch (error) {
                        if (error?.name === 'AbortError') {
                            return;
                        }
                    }
                }

                try {
                    await writeTextToClipboard(shareUrl);
                    setStatus('Trình duyệt chưa hỗ trợ chia sẻ nhanh, liên kết đã được sao chép.');
                } catch (error) {
                    setStatus('Trình duyệt chưa hỗ trợ chia sẻ nhanh. Vui lòng dùng các nút mạng xã hội bên cạnh.');
                }
            });
        });
    });
}

function withAutoplay(url) {
    if (!url) {
        return '';
    }

    return `${url}${url.includes('?') ? '&' : '?'}autoplay=1&rel=0`;
}

function initGalleryLightbox() {
    if (galleryLightboxInitialized) {
        return;
    }

    galleryLightboxInitialized = true;

    const resolveLightboxElements = () => {
        const modal = document.querySelector('[data-frontsite-gallery-lightbox]');

        if (! modal) {
            return null;
        }

        return {
            modal,
            image: modal.querySelector('[data-gallery-image]'),
            video: modal.querySelector('[data-gallery-video]'),
            iframe: modal.querySelector('[data-gallery-iframe]'),
            badge: modal.querySelector('[data-gallery-badge]'),
            title: modal.querySelector('[data-gallery-title]'),
            description: modal.querySelector('[data-gallery-description]'),
            counter: modal.querySelector('[data-gallery-counter]'),
            close: modal.querySelector('[data-gallery-close]'),
            prevButtons: Array.from(modal.querySelectorAll('[data-gallery-prev]')),
            nextButtons: Array.from(modal.querySelectorAll('[data-gallery-next]')),
        };
    };

    const isLightboxOpen = () => {
        const elements = resolveLightboxElements();

        return elements ? elements.modal.getAttribute('aria-hidden') === 'false' : false;
    };

    const galleryCollectionKey = (trigger) => {
        if (! trigger) {
            return '__page__';
        }

        return trigger.dataset.galleryGroup
            || trigger.closest('[data-gallery-collection]')?.dataset.galleryCollection
            || '__page__';
    };

    const collectGalleryTriggers = (trigger) => {
        const collectionKey = galleryCollectionKey(trigger);
        const allTriggers = Array.from(document.querySelectorAll('[data-gallery-trigger]'));

        if (collectionKey === '__page__') {
            return allTriggers;
        }

        return allTriggers.filter((item) => galleryCollectionKey(item) === collectionKey);
    };

    const resetMediaState = (elements) => {
        elements.image?.classList.add('hidden');
        elements.video?.classList.add('hidden');
        elements.iframe?.classList.add('hidden');

        if (elements.image) {
            elements.image.removeAttribute('src');
            elements.image.removeAttribute('alt');
        }

        if (elements.video) {
            elements.video.pause();
            elements.video.removeAttribute('src');
            elements.video.removeAttribute('poster');
            elements.video.load();
        }

        if (elements.iframe) {
            elements.iframe.setAttribute('src', '');
        }
    };

    const syncNavigationState = (elements) => {
        const total = activeGalleryLightboxTriggers.length;
        const hasNavigation = total > 1;
        const counterLabel = total > 0 && activeGalleryLightboxIndex >= 0
            ? `${activeGalleryLightboxIndex + 1} / ${total}`
            : '0 / 0';

        if (elements.counter) {
            elements.counter.textContent = counterLabel;
        }

        [...elements.prevButtons, ...elements.nextButtons].forEach((button) => {
            button.disabled = ! hasNavigation;
        });
    };

    const renderActiveGalleryItem = () => {
        const elements = resolveLightboxElements();

        if (! elements || activeGalleryLightboxIndex < 0 || ! activeGalleryLightboxTriggers.length) {
            return;
        }

        const trigger = activeGalleryLightboxTriggers[activeGalleryLightboxIndex];

        if (! trigger) {
            return;
        }

        const kind = trigger.dataset.galleryKind || 'image';
        const src = trigger.dataset.gallerySrc || '';
        const embed = trigger.dataset.galleryEmbed || '';
        const poster = trigger.dataset.galleryPoster || '';

        resetMediaState(elements);

        if (elements.badge) {
            elements.badge.innerHTML = kind === 'youtube'
                ? '<i class="fa-brands fa-youtube"></i>YouTube'
                : kind === 'mp4'
                    ? '<i class="fa-solid fa-video"></i>Video'
                    : '<i class="fa-regular fa-image"></i>Ảnh';
        }

        if (elements.title) {
            elements.title.textContent = trigger.dataset.galleryTitle || '';
        }

        if (elements.description) {
            elements.description.textContent = trigger.dataset.galleryDescription || '';
        }

        if (kind === 'youtube' && elements.iframe && embed) {
            elements.iframe.classList.remove('hidden');
            elements.iframe.setAttribute('src', withAutoplay(embed));
        } else if (kind === 'mp4' && elements.video && src) {
            elements.video.classList.remove('hidden');
            elements.video.setAttribute('src', src);

            if (poster) {
                elements.video.setAttribute('poster', poster);
            }

            elements.video.load();
            elements.video.play().catch(() => {});
        } else if (elements.image && src) {
            elements.image.classList.remove('hidden');
            elements.image.setAttribute('src', src);
            elements.image.setAttribute('alt', trigger.dataset.galleryAlt || trigger.dataset.galleryTitle || 'Ảnh gallery');
        }

        syncNavigationState(elements);
    };

    const close = () => {
        const elements = resolveLightboxElements();

        if (! elements) {
            return;
        }

        elements.modal.classList.add('hidden');
        elements.modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('service-modal-open');
        resetMediaState(elements);
        activeGalleryLightboxTriggers = [];
        activeGalleryLightboxIndex = -1;
        syncNavigationState(elements);

        if (activeGalleryLightboxFocusTarget instanceof HTMLElement && document.contains(activeGalleryLightboxFocusTarget)) {
            activeGalleryLightboxFocusTarget.focus();
        }

        activeGalleryLightboxFocusTarget = null;
    };

    const step = (direction) => {
        if (activeGalleryLightboxTriggers.length <= 1) {
            return;
        }

        activeGalleryLightboxIndex = (activeGalleryLightboxIndex + direction + activeGalleryLightboxTriggers.length) % activeGalleryLightboxTriggers.length;
        renderActiveGalleryItem();
    };

    const open = (trigger) => {
        const elements = resolveLightboxElements();

        if (! elements) {
            return;
        }

        activeGalleryLightboxFocusTarget = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
        activeGalleryLightboxTriggers = collectGalleryTriggers(trigger);
        activeGalleryLightboxIndex = activeGalleryLightboxTriggers.indexOf(trigger);

        if (activeGalleryLightboxIndex < 0) {
            activeGalleryLightboxTriggers = [trigger];
            activeGalleryLightboxIndex = 0;
        }

        renderActiveGalleryItem();

        elements.modal.classList.remove('hidden');
        elements.modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('service-modal-open');

        window.requestAnimationFrame(() => {
            elements.close?.focus();
        });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-gallery-trigger]');

        if (trigger) {
            event.preventDefault();
            open(trigger);

            return;
        }

        if (event.target.closest('[data-gallery-prev]')) {
            event.preventDefault();
            step(-1);

            return;
        }

        if (event.target.closest('[data-gallery-next]')) {
            event.preventDefault();
            step(1);

            return;
        }

        if (event.target.closest('[data-gallery-close], [data-gallery-backdrop]')) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (! isLightboxOpen()) {
            return;
        }

        if (event.key === 'Escape') {
            close();

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
}

function initHeroSliders() {
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
        const slides = Array.from(slider.querySelectorAll('[data-hero-slide-item]'));
        const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));
        const prev = slider.querySelector('[data-hero-prev]');
        const next = slider.querySelector('[data-hero-next]');
        const autoplayEnabled = slider.dataset.autoplay !== 'false' && ! prefersReducedMotion.matches;

        if (slides.length === 0) {
            return;
        }

        if (slides.length === 1) {
            setHeroSlideInteractiveState(slides[0], true);
            hydrateHeroSlideMedia(slides[0]);
            animateHeroTextReveal(slides[0]);

            return;
        }

        const interval = Number(slider.dataset.interval || 5500);
        let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
        let timer;
        let autoplayStarted = false;

        const showSlide = (index) => {
            activeIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === activeIndex;

                setHeroSlideInteractiveState(slide, isActive);

                if (isActive) {
                    hydrateHeroSlideMedia(slide);
                    animateHeroTextReveal(slide);
                } else {
                    resetHeroTextReveal(slide);
                }
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === activeIndex);
                dot.setAttribute('aria-current', dotIndex === activeIndex ? 'true' : 'false');
            });
        };

        const restart = () => {
            if (! autoplayEnabled || ! autoplayStarted) {
                return;
            }

            window.clearInterval(timer);
            timer = window.setInterval(() => showSlide(activeIndex + 1), interval);
        };
        const startAutoplay = () => {
            if (autoplayStarted) {
                return;
            }

            autoplayStarted = true;
            restart();
        };

        prev?.addEventListener('click', () => {
            startAutoplay();
            showSlide(activeIndex - 1);
            restart();
        });

        next?.addEventListener('click', () => {
            startAutoplay();
            showSlide(activeIndex + 1);
            restart();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                startAutoplay();
                showSlide(index);
                restart();
            });
        });

        slider.addEventListener('mouseenter', () => window.clearInterval(timer));
        slider.addEventListener('mouseleave', restart);
        slider.addEventListener('focusin', startAutoplay, { once: true });
        slider.addEventListener('pointerdown', startAutoplay, { once: true });
        slider.addEventListener('touchstart', startAutoplay, { once: true, passive: true });

        showSlide(activeIndex);
    });
}

function hydrateHeroSlideMedia(slide) {
    if (! slide || slide.dataset.heroMediaHydrated === 'true') {
        return;
    }

    slide.querySelectorAll('[data-hero-source-srcset]').forEach((source) => {
        if (! (source instanceof HTMLSourceElement)) {
            return;
        }

        const srcset = source.dataset.heroSourceSrcset?.trim();

        if (! srcset) {
            return;
        }

        source.srcset = srcset;
        delete source.dataset.heroSourceSrcset;
    });

    slide.querySelectorAll('[data-hero-image]').forEach((image) => {
        if (! (image instanceof HTMLImageElement)) {
            return;
        }

        const src = image.dataset.heroImageSrc?.trim();
        const srcset = image.dataset.heroImageSrcset?.trim();

        if (src) {
            image.src = src;
            delete image.dataset.heroImageSrc;
        }

        if (srcset) {
            image.srcset = srcset;
            delete image.dataset.heroImageSrcset;
        }
    });

    slide.querySelectorAll('[data-hero-iframe-src]').forEach((iframe) => {
        if (! (iframe instanceof HTMLIFrameElement)) {
            return;
        }

        const src = iframe.dataset.heroIframeSrc?.trim();

        if (! src) {
            return;
        }

        iframe.src = src;
    });

    slide.querySelectorAll('[data-hero-video]').forEach((video) => {
        if (! (video instanceof HTMLVideoElement)) {
            return;
        }

        const src = video.dataset.heroVideoSrc?.trim();

        if (src) {
            video.src = src;
            delete video.dataset.heroVideoSrc;
            video.load();
        }

        video.play().catch(() => {});
    });

    slide.dataset.heroMediaHydrated = 'true';
}

function initTourDetailExpandables() {
    document.querySelectorAll('[data-tour-details-expandable-block]').forEach((block) => {
        const existingController = tourDetailExpandableControllers.get(block);

        if (existingController) {
            existingController.sync();

            return;
        }

        const panel = block.querySelector('[data-tour-details-expandable]');
        const fade = block.querySelector('[data-tour-details-fade]');
        const toggle = block.querySelector('[data-tour-details-toggle]');
        const label = block.querySelector('[data-tour-details-toggle-label]');
        const icon = block.querySelector('[data-tour-details-toggle-icon]');

        if (! panel || ! fade || ! toggle || ! label || ! icon) {
            return;
        }

        const collapsedHeight = () => {
            const mobileHeight = Number.parseInt(block.dataset.collapsedHeightMobile ?? '400', 10) || 400;
            const desktopHeight = Number.parseInt(block.dataset.collapsedHeightDesktop ?? '800', 10) || 800;

            return window.innerWidth >= 1024 ? desktopHeight : mobileHeight;
        };

        const sync = () => {
            const maxHeight = collapsedHeight();
            const needsToggle = panel.scrollHeight > maxHeight + 8;
            const isExpanded = block.dataset.expanded === 'true';

            if (! needsToggle) {
                block.dataset.expanded = 'false';
                panel.style.maxHeight = 'none';
                fade.classList.add('hidden');
                toggle.hidden = true;
                toggle.classList.remove('invisible');
                toggle.setAttribute('aria-hidden', 'true');
                toggle.tabIndex = -1;
                toggle.setAttribute('aria-expanded', 'false');
                label.textContent = 'Xem thêm';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');

                return;
            }

            toggle.hidden = false;
            toggle.classList.remove('invisible');
            toggle.setAttribute('aria-hidden', 'false');
            toggle.tabIndex = 0;
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            label.textContent = isExpanded ? 'Thu gọn' : 'Xem thêm';
            icon.classList.toggle('fa-chevron-down', ! isExpanded);
            icon.classList.toggle('fa-chevron-up', isExpanded);
            fade.classList.toggle('hidden', isExpanded);
            panel.style.maxHeight = isExpanded ? `${panel.scrollHeight}px` : `${maxHeight}px`;
        };

        toggle.addEventListener('click', () => {
            block.dataset.expanded = block.dataset.expanded === 'true' ? 'false' : 'true';
            sync();
        });

        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(() => {
                sync();
            });

            observer.observe(panel);
        }

        panel.querySelectorAll('img').forEach((image) => {
            if (! image.complete) {
                image.addEventListener('load', sync, { once: true });
                image.addEventListener('error', sync, { once: true });
            }
        });

        tourDetailExpandableControllers.set(block, { sync });
        sync();
    });
}

export function initFrontsiteInteractions() {
    document.documentElement.classList.add('js-frontsite-motion');
    initGalleryLightbox();
    initHeroSliders();
    initTourDetailExpandables();
    initCardCarousels();
    initTourItineraryDetails();
    initFaqAccordions();
    initTextReveals();
    initConsultationModal();
    initSocialShareBlocks();

    if (! interactionRefreshBound) {
        interactionRefreshBound = true;

        document.addEventListener('frontsite:refresh-card-carousels', () => {
            window.requestAnimationFrame(() => {
                initTourDetailExpandables();
                initCardCarousels();
                initTourItineraryDetails();
    initFaqAccordions();
                initTextReveals();
                initSocialShareBlocks();
            });
        });
    }
}
