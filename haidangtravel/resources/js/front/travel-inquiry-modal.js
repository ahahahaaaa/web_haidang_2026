export function initTravelInquiryModal() {
    const modal = document.querySelector('[data-travel-inquiry-modal]');

    if (! modal || modal.dataset.initialized === 'true') {
        return;
    }

    modal.dataset.initialized = 'true';

    const titleTarget = modal.querySelector('[data-travel-inquiry-modal-title]');
    const descriptionTarget = modal.querySelector('[data-travel-inquiry-modal-description]');
    const dialog = modal.querySelector('[data-travel-inquiry-dialog]');
    const feedbackTarget = modal.querySelector('[data-travel-inquiry-feedback]');
    const inquiryForm = modal.querySelector('form[data-frontsite-ajax-form]');
    const sourceInput = modal.querySelector('[data-travel-inquiry-source-input]');
    const tourIdInput = modal.querySelector('[data-travel-inquiry-tour-id-input]');
    const serviceIdInput = modal.querySelector('[data-travel-inquiry-service-id-input]');
    const contextInput = modal.querySelector('[data-travel-inquiry-context-input]');
    const subjectInput = modal.querySelector('[data-travel-inquiry-subject-input]');
    const pageUrlInput = modal.querySelector('[data-travel-inquiry-page-url-input]');
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');
    let lastActiveElement = null;

    if (! dialog) {
        return;
    }

    const defaultState = {
        context: modal.dataset.defaultContext || contextInput?.value || 'Liên hệ chung',
        description: modal.dataset.defaultDescription || descriptionTarget?.textContent || '',
        source: modal.dataset.defaultSource || sourceInput?.value || 'general',
        subject: modal.dataset.defaultSubject || subjectInput?.value || 'Tư vấn du lịch',
        title: modal.dataset.defaultTitle || titleTarget?.textContent || '',
    };

    const focusableElements = () => Array.from(dialog.querySelectorAll(focusableSelector))
        .filter((element) => element instanceof HTMLElement)
        .filter((element) => ! element.hasAttribute('hidden') && (element.offsetParent !== null || document.activeElement === element));

    const focusInitialTarget = () => {
        window.requestAnimationFrame(() => {
            if (modal.dataset.feedbackFocusOnLoad === 'true' && feedbackTarget instanceof HTMLElement) {
                feedbackTarget.focus();
                return;
            }

            const firstFocusable = focusableElements()[0];
            const firstInvalid = dialog.querySelector('[aria-invalid="true"]');
            const nextTarget = firstInvalid instanceof HTMLElement
                ? firstInvalid
                : (firstFocusable instanceof HTMLElement ? firstFocusable : dialog);

            nextTarget.focus();
        });
    };

    const setOpenState = (isOpen) => {
        modal.classList.toggle('hidden', ! isOpen);
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.classList.toggle('service-modal-open', isOpen);
    };

    const syncFromTrigger = (trigger = null) => {
        const title = trigger?.dataset.travelInquiryModalTitle || defaultState.title;
        const description = trigger?.dataset.travelInquiryModalDescription || defaultState.description;
        const source = trigger?.dataset.travelInquirySource || defaultState.source;
        const tourId = trigger?.dataset.travelInquiryTourId || '';
        const serviceId = trigger?.dataset.travelInquiryServiceId || '';
        const context = trigger?.dataset.travelInquiryContext || defaultState.context;
        const subject = trigger?.dataset.travelInquirySubject || defaultState.subject;

        if (titleTarget) {
            titleTarget.textContent = title;
        }

        if (descriptionTarget) {
            descriptionTarget.textContent = description;
        }

        if (sourceInput) {
            sourceInput.value = source;
        }

        if (tourIdInput) {
            tourIdInput.value = tourId;
        }

        if (serviceIdInput) {
            serviceIdInput.value = serviceId;
        }

        if (contextInput) {
            contextInput.value = context;
        }

        if (subjectInput && modal.dataset.preserveOldSubject !== 'true') {
            subjectInput.value = subject;
        }

        if (pageUrlInput) {
            pageUrlInput.value = window.location.href;
        }
    };

    const open = (trigger = null) => {
        lastActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        if (trigger && window.FrontsiteAjaxForms?.clearFormState && inquiryForm instanceof HTMLFormElement) {
            window.FrontsiteAjaxForms.clearFormState(inquiryForm);
        }

        syncFromTrigger(trigger);
        setOpenState(true);
        focusInitialTarget();
        document.dispatchEvent(new CustomEvent('frontsite:travel-inquiry-opened', {
            detail: {
                root: modal,
                trigger,
            },
        }));
    };

    const close = () => {
        setOpenState(false);
        modal.dataset.feedbackFocusOnLoad = 'false';

        if (lastActiveElement instanceof HTMLElement && document.contains(lastActiveElement)) {
            lastActiveElement.focus();
        }
    };

    document.querySelectorAll('[data-travel-inquiry-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            if (trigger.tagName === 'A') {
                event.preventDefault();
            }

            modal.dataset.preserveOldSubject = 'false';
            open(trigger);
        });
    });

    modal.querySelectorAll('[data-travel-inquiry-close], [data-travel-inquiry-backdrop]').forEach((trigger) => {
        trigger.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (modal.getAttribute('aria-hidden') !== 'false') {
            return;
        }

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const elements = focusableElements();

        if (elements.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }

        const firstElement = elements[0];
        const lastElement = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (! event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });

    if (modal.dataset.openOnLoad === 'true') {
        modal.dataset.preserveOldSubject = 'true';
        syncFromTrigger();
        setOpenState(true);
        focusInitialTarget();
        document.dispatchEvent(new CustomEvent('frontsite:travel-inquiry-opened', {
            detail: {
                root: modal,
                trigger: null,
            },
        }));
    }
}
