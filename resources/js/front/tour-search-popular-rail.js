const rootSelector = '[data-tour-search-popular], [data-tour-card-departure-rail], [data-popular-search-rail]';
const trackSelector = '[data-tour-search-popular-track], [data-tour-card-departure-track], [data-popular-search-rail-track]';
const previousSelector = '[data-tour-search-popular-prev], [data-tour-card-departure-prev], [data-popular-search-rail-prev]';
const nextSelector = '[data-tour-search-popular-next], [data-tour-card-departure-next], [data-popular-search-rail-next]';
const initializedRoots = new WeakSet();

function initRail(root) {
    if (initializedRoots.has(root)) {
        return;
    }

    const track = root.querySelector(trackSelector);
    const previousButton = root.querySelector(previousSelector);
    const nextButton = root.querySelector(nextSelector);

    if (!(track instanceof HTMLElement) || !(previousButton instanceof HTMLButtonElement) || !(nextButton instanceof HTMLButtonElement)) {
        return;
    }

    initializedRoots.add(root);

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const edgeTolerance = 2;

    const syncButtons = () => {
        const maximumScrollLeft = Math.max(track.scrollWidth - track.clientWidth, 0);

        previousButton.disabled = track.scrollLeft <= edgeTolerance;
        nextButton.disabled = track.scrollLeft >= maximumScrollLeft - edgeTolerance;
    };

    const move = (direction) => {
        track.scrollBy({
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
            left: direction * Math.max(Math.round(track.clientWidth * 0.8), 180),
        });
    };

    previousButton.addEventListener('click', () => move(-1));
    nextButton.addEventListener('click', () => move(1));
    track.addEventListener('scroll', syncButtons, { passive: true });
    root.addEventListener('frontsite:chip-rail-changed', () => {
        window.requestAnimationFrame(() => {
            track.scrollLeft = 0;
            syncButtons();
        });
    });

    if ('ResizeObserver' in window) {
        new ResizeObserver(syncButtons).observe(track);
    }

    window.requestAnimationFrame(syncButtons);
}

export function initHorizontalChipRails(root = document) {
    root.querySelectorAll(rootSelector).forEach(initRail);
}

export const initTourSearchPopularRails = initHorizontalChipRails;
