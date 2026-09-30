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
    const departureIdInput = modal.querySelector('[data-travel-inquiry-departure-id-input]');
    const flashSaleSlugInput = modal.querySelector('[data-travel-inquiry-flash-sale-slug-input]');
    const serviceIdInput = modal.querySelector('[data-travel-inquiry-service-id-input]');
    const contextInput = modal.querySelector('[data-travel-inquiry-context-input]');
    const subjectInput = modal.querySelector('[data-travel-inquiry-subject-input]');
    const pageUrlInput = modal.querySelector('[data-travel-inquiry-page-url-input]');
    const voucherCampaignInput = modal.querySelector('[data-travel-inquiry-voucher-campaign-input]');
    const voucherVariantInput = modal.querySelector('[data-travel-inquiry-voucher-variant-input]')
        || modal.querySelector('[data-travel-inquiry-ab-variant-input]');
    const pricingNotice = modal.querySelector('[data-travel-inquiry-pricing-notice]');
    const pricingNoticeTitle = modal.querySelector('[data-travel-inquiry-pricing-notice-title]');
    const pricingNoticeMessage = modal.querySelector('[data-travel-inquiry-pricing-notice-message]');
    const ticketNote = modal.querySelector('[data-travel-inquiry-ticket-note]');
    const ticketLabels = modal.querySelectorAll('[data-travel-inquiry-ticket-label]');
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
        departureId: departureIdInput?.value || '',
        description: modal.dataset.defaultDescription || descriptionTarget?.textContent || '',
        flashSaleSlug: flashSaleSlugInput?.value || '',
        source: modal.dataset.defaultSource || sourceInput?.value || 'general',
        subject: modal.dataset.defaultSubject || subjectInput?.value || 'Tư vấn du lịch',
        title: modal.dataset.defaultTitle || titleTarget?.textContent || '',
        voucherCampaign: modal.dataset.defaultVoucherCampaign || voucherCampaignInput?.value || '',
        voucherVariant: modal.dataset.defaultVoucherVariant || modal.dataset.defaultAbVariant || voucherVariantInput?.value || '',
    };
    const shouldOpenVoucherFromHash = () => window.location.hash === '#nhan-voucher'
        && defaultState.voucherCampaign !== '';

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
        const departureId = trigger
            ? (trigger.dataset.travelInquiryDepartureId || '')
            : defaultState.departureId;
        const flashSaleSlug = trigger
            ? (trigger.dataset.travelInquiryFlashSaleSlug || '')
            : defaultState.flashSaleSlug;
        const serviceId = trigger?.dataset.travelInquiryServiceId || '';
        const context = trigger?.dataset.travelInquiryContext || defaultState.context;
        const subject = trigger?.dataset.travelInquirySubject || defaultState.subject;
        const voucherCampaign = trigger?.dataset.travelInquiryVoucherCampaign || defaultState.voucherCampaign;
        const voucherVariant = trigger?.dataset.travelInquiryVoucherVariant || trigger?.dataset.travelInquiryAbVariant || defaultState.voucherVariant;

        if (titleTarget) {
            titleTarget.textContent = title;
        }

        if (descriptionTarget) {
            descriptionTarget.textContent = description;
        }

        if (sourceInput) {
            sourceInput.value = source;
        }

        if (ticketNote instanceof HTMLElement) {
            ticketNote.classList.toggle('hidden', source !== 'tour');
        }

        ticketLabels.forEach((label) => {
            label.classList.toggle('hidden', source !== 'tour');
        });

        if (tourIdInput) {
            tourIdInput.value = tourId;
        }

        if (departureIdInput) {
            departureIdInput.value = departureId;
        }

        if (flashSaleSlugInput) {
            flashSaleSlugInput.value = flashSaleSlug;
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

        if (voucherCampaignInput) {
            voucherCampaignInput.value = voucherCampaign;
        }

        if (voucherVariantInput) {
            voucherVariantInput.value = voucherVariant;
        }

        syncPricingNotice(trigger, source);
    };

    const syncPricingNotice = (trigger, source) => {
        if (! (pricingNotice instanceof HTMLElement)) {
            return;
        }

        const priceLabel = trigger?.dataset.travelInquiryPriceLabel || '';
        const regularPriceLabel = trigger?.dataset.travelInquiryRegularPriceLabel || priceLabel;
        const remainingTickets = trigger?.dataset.travelInquiryFlashTicketsRemaining || '';
        const isFlashSale = trigger?.dataset.travelInquiryPriceType === 'flash_sale';
        const isFlashSaleRequested = (flashSaleSlugInput?.value || '') !== '';

        if (source !== 'tour' || priceLabel === '') {
            pricingNotice.classList.add('hidden');

            return;
        }

        pricingNotice.classList.remove('hidden');

        if (pricingNoticeTitle instanceof HTMLElement) {
            pricingNoticeTitle.textContent = isFlashSale
                ? `Giá Flash Sale: ${priceLabel}/khách`
                : (isFlashSaleRequested
                    ? `Giá thường hiện tại: ${priceLabel}/khách`
                    : `Giá hiện tại: ${priceLabel}/khách`);
        }

        if (pricingNoticeMessage instanceof HTMLElement) {
            pricingNoticeMessage.textContent = isFlashSale
                ? `Còn ${remainingTickets || '0'} vé Flash Sale. Tổng vé bằng số người lớn cộng số trẻ em. Hệ thống sẽ kiểm tra lại khi gửi; nếu không đủ vé, yêu cầu được ghi nhận theo giá thường ${regularPriceLabel}/khách.`
                : (isFlashSaleRequested
                    ? 'Flash Sale từ liên kết này hiện không còn hiển thị. Hệ thống vẫn kiểm tra lại khi gửi và sẽ báo rõ giá được ghi nhận.'
                    : 'Tổng vé bằng số người lớn cộng số trẻ em. Giá sẽ được hệ thống kiểm tra lại và ghi nhận tại đúng thời điểm bạn gửi form.');
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

    const openFromPageState = () => {
        if (modal.getAttribute('aria-hidden') === 'false') {
            return;
        }

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
    };

    document.querySelectorAll('[data-voucher-claim-link]').forEach((link) => {
        if (! (link instanceof HTMLAnchorElement)) {
            return;
        }

        link.addEventListener('click', (event) => {
            const targetUrl = new URL(link.href, window.location.href);
            const currentUrl = new URL(window.location.href);
            const targetsCurrentLanding = targetUrl.origin === currentUrl.origin
                && targetUrl.pathname === currentUrl.pathname
                && targetUrl.search === currentUrl.search
                && targetUrl.hash === '#nhan-voucher';

            if (! targetsCurrentLanding || defaultState.voucherCampaign === '') {
                return;
            }

            event.preventDefault();
            window.history.pushState({}, '', targetUrl);
            modal.dataset.preserveOldSubject = 'true';
            open(link);
        });
    });

    if (modal.dataset.openOnLoad === 'true' || shouldOpenVoucherFromHash()) {
        openFromPageState();
    }

    window.addEventListener('hashchange', () => {
        if (shouldOpenVoucherFromHash()) {
            openFromPageState();
        }
    });
}
