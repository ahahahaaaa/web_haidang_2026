const popupSelector = '[data-home-popup-slider]';
const storagePrefix = 'haidangtravel:home-popup-dismissed:';
const initializedPopups = new WeakSet();
const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

function storageKey(root) {
    return `${storagePrefix}${root.dataset.homePopupKey || 'home-popup'}`;
}

function isDismissed(root) {
    try {
        const expiresAt = Number(window.localStorage.getItem(storageKey(root)) || 0);

        return Number.isFinite(expiresAt) && expiresAt > Date.now();
    } catch (error) {
        return false;
    }
}

function rememberDismissal(root) {
    const dismissHours = Math.max(Number(root.dataset.homePopupDismissHours || 24), 1);

    try {
        window.localStorage.setItem(storageKey(root), String(Date.now() + dismissHours * 60 * 60 * 1000));
    } catch (error) {
        // Ignore unavailable storage; the popup can still be closed for this page view.
    }
}

function focusableElements(dialog) {
    return Array.from(dialog.querySelectorAll(focusableSelector))
        .filter((element) => element instanceof HTMLElement)
        .filter((element) => ! element.hasAttribute('hidden') && (element.offsetParent !== null || document.activeElement === element));
}

function initPopup(root) {
    if (initializedPopups.has(root)) {
        return;
    }

    initializedPopups.add(root);

    const dialog = root.querySelector('[data-home-popup-dialog]');
    const slides = Array.from(root.querySelectorAll('[data-home-popup-slide]'));
    const counter = root.querySelector('[data-home-popup-counter]');
    const prev = root.querySelector('[data-home-popup-prev]');
    const next = root.querySelector('[data-home-popup-next]');
    const interval = Math.max(Number.parseInt(root.dataset.homePopupInterval || '0', 10) || 0, 0);
    let activeIndex = 0;
    let lastActiveElement = null;
    let timer = null;

    if (! dialog || slides.length === 0) {
        return;
    }

    const syncSlide = (index) => {
        activeIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === activeIndex;

            slide.classList.toggle('hidden', ! isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            slide.toggleAttribute('inert', ! isActive);
        });

        if (counter) {
            counter.textContent = `${activeIndex + 1} / ${slides.length}`;
        }
    };

    const clearAutoplay = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const startAutoplay = () => {
        clearAutoplay();

        if (interval <= 0 || slides.length < 2) {
            return;
        }

        timer = window.setInterval(() => {
            syncSlide(activeIndex + 1);
        }, interval);
    };

    const close = (remember = true) => {
        clearAutoplay();
        root.classList.add('hidden');
        root.classList.remove('flex');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('service-modal-open');

        if (remember) {
            rememberDismissal(root);
        }

        if (lastActiveElement instanceof HTMLElement && document.contains(lastActiveElement)) {
            lastActiveElement.focus();
        }
    };

    const open = () => {
        if (isDismissed(root)) {
            return;
        }

        lastActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        syncSlide(activeIndex);
        root.classList.remove('hidden');
        root.classList.add('flex');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('service-modal-open');
        startAutoplay();

        window.requestAnimationFrame(() => {
            const firstFocusable = focusableElements(dialog)[0];

            (firstFocusable instanceof HTMLElement ? firstFocusable : dialog).focus();
        });
    };

    const scheduleOpen = () => {
        if (isDismissed(root)) {
            return;
        }

        const delay = Math.max(Number.parseInt(root.dataset.homePopupDelay || '2000', 10) || 2000, 0);

        window.setTimeout(open, delay);
    };

    prev?.addEventListener('click', () => {
        syncSlide(activeIndex - 1);
        startAutoplay();
    });

    next?.addEventListener('click', () => {
        syncSlide(activeIndex + 1);
        startAutoplay();
    });

    root.querySelectorAll('[data-home-popup-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => close(true));
    });

    dialog.addEventListener('mouseenter', clearAutoplay);
    dialog.addEventListener('mouseleave', startAutoplay);
    dialog.addEventListener('focusin', clearAutoplay);
    dialog.addEventListener('focusout', (event) => {
        if (! dialog.contains(event.relatedTarget)) {
            startAutoplay();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (root.getAttribute('aria-hidden') !== 'false') {
            return;
        }

        if (event.key === 'Escape') {
            close(true);
            return;
        }

        if (event.key === 'ArrowLeft' && slides.length > 1) {
            event.preventDefault();
            syncSlide(activeIndex - 1);
            startAutoplay();
            return;
        }

        if (event.key === 'ArrowRight' && slides.length > 1) {
            event.preventDefault();
            syncSlide(activeIndex + 1);
            startAutoplay();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const elements = focusableElements(dialog);

        if (elements.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    syncSlide(0);

    if (document.readyState === 'complete') {
        scheduleOpen();
    } else {
        window.addEventListener('load', scheduleOpen, { once: true });
    }
}

export function initHomePopupSlider() {
    document.querySelectorAll(popupSelector).forEach(initPopup);
}
