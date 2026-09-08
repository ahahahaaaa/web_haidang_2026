import { initFrontsiteAjaxForms } from './front/frontsite-ajax-forms';
import { initTravelInquiryModal } from './front/travel-inquiry-modal';

const INTERACTION_SELECTOR = [
    '[data-reveal]',
    '[data-hero-slider]',
    '[data-card-carousel]',
    '[data-faq-accordion]',
    '[data-frontsite-gallery-lightbox]',
    '[data-tour-details-expandable-block]',
    '[data-frontsite-consultation-modal]',
    '[data-service-consultation-modal]',
    '[data-consultation-open]',
    '[data-social-share]',
].join(', ');
const TAB_SELECTOR = '[data-home-featured-tabs], [data-tour-list-tabs], [data-tour-departure-tabs]';
const TOUR_GALLERY_SELECTOR = '[data-tour-gallery]';
const FORM_CONTROL_SELECTOR = '[data-frontsite-select], [data-frontsite-datepicker]';
const moduleCache = new Map();

function onDomReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });

        return;
    }

    callback();
}

function loadModule(key, importer) {
    if (! moduleCache.has(key)) {
        moduleCache.set(key, importer());
    }

    return moduleCache.get(key);
}

function hasVisibleFormControls(root = document) {
    return Array.from(root.querySelectorAll(FORM_CONTROL_SELECTOR)).some((element) => {
        const modal = element.closest('[data-travel-inquiry-modal]');

        return ! modal || modal.getAttribute('aria-hidden') === 'false';
    });
}

function closestRefreshRoot(element) {
    return element.closest('[data-travel-inquiry-modal], form, section, article, div') ?? document;
}

function loadFrontsiteFormControls(root = document) {
    return loadModule('frontsite-form-controls', () => import('./front/frontsite-form-controls.js'))
        .then((module) => {
            module.initFrontsiteFormControls(root);

            return module;
        });
}

function loadFrontsiteInteractions() {
    return loadModule('frontsite-interactions', () => import('./front/service-detail.js'))
        .then((module) => {
            module.initFrontsiteInteractions();

            return module;
        });
}

function loadFrontsiteTabs() {
    return loadModule('frontsite-tabs', () => import('./front/home-featured-tabs.js'))
        .then((module) => {
            module.initTabbedTourLists();

            return module;
        });
}

function loadTourGallerySliders() {
    return loadModule('tour-gallery-slider', () => import('./front/tour-gallery-slider.js'))
        .then((module) => {
            module.initTourGallerySliders();

            return module;
        });
}

function bindDeferredFrontsiteControls() {
    document.addEventListener('frontsite:travel-inquiry-opened', (event) => {
        void loadFrontsiteFormControls(event.detail?.root ?? document);
    });

    document.addEventListener('frontsite:refresh-controls', (event) => {
        void loadFrontsiteFormControls(event.detail?.root ?? document);
    });

    document.addEventListener('focusin', (event) => {
        const target = event.target;

        if (! (target instanceof Element)) {
            return;
        }

        if (! target.matches(FORM_CONTROL_SELECTOR) && ! target.closest(FORM_CONTROL_SELECTOR)) {
            return;
        }

        void loadFrontsiteFormControls(closestRefreshRoot(target));
    });
}

function bootFrontsiteRuntime() {
    bindDeferredFrontsiteControls();

    initFrontsiteAjaxForms();
    initTravelInquiryModal();

    const eagerLoads = [];

    if (document.querySelector(INTERACTION_SELECTOR)) {
        eagerLoads.push(loadFrontsiteInteractions());
    }

    if (document.querySelector(TAB_SELECTOR)) {
        eagerLoads.push(loadFrontsiteTabs());
    }

    if (document.querySelector(TOUR_GALLERY_SELECTOR)) {
        eagerLoads.push(loadTourGallerySliders());
    }

    if (hasVisibleFormControls()) {
        eagerLoads.push(loadFrontsiteFormControls(document));
    }

    if (eagerLoads.length > 0) {
        void Promise.allSettled(eagerLoads);
    }
}

onDomReady(bootFrontsiteRuntime);
