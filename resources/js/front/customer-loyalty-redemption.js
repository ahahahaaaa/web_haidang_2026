const FORM_SELECTOR = 'form[data-customer-loyalty-redemption-form]';
const MODAL_SELECTOR = '[data-customer-loyalty-redemption-modal]';
const PANEL_SELECTORS = [
    '[data-customer-loyalty-points-card]',
    '[data-customer-loyalty-redemptions]',
    '[data-customer-loyalty-gifts]',
    '[data-customer-loyalty-orders]',
];

let listenersBound = false;
let activeForm = null;
let isSubmitting = false;
let submitLocked = false;

export function initCustomerLoyaltyRedemptions() {
    if (listenersBound) {
        return;
    }

    listenersBound = true;

    document.addEventListener('submit', handleFormSubmit);
    document.addEventListener('click', handleModalClick);
    document.addEventListener('keydown', handleModalKeydown);
}

function handleFormSubmit(event) {
    const form = event.target;

    if (! (form instanceof HTMLFormElement) || ! form.matches(FORM_SELECTOR)) {
        return;
    }

    const modal = redemptionModal();

    if (! modal) {
        return;
    }

    event.preventDefault();
    activeForm = form;
    submitLocked = false;
    setModalContent(form);
    setFeedback();
    setSubmitButtonState(false);
    openModal(modal);
}

function handleModalClick(event) {
    const target = event.target;

    if (! (target instanceof Element)) {
        return;
    }

    if (target.closest('[data-customer-loyalty-redemption-submit]')) {
        void submitRedemptionRequest();

        return;
    }

    if (target.closest('[data-customer-loyalty-redemption-close], [data-customer-loyalty-redemption-backdrop]')) {
        closeModal();
    }
}

function handleModalKeydown(event) {
    if (event.key !== 'Escape' || ! isModalOpen()) {
        return;
    }

    closeModal();
}

async function submitRedemptionRequest() {
    if (! activeForm || isSubmitting || submitLocked) {
        return;
    }

    isSubmitting = true;
    setSubmitButtonState(true);
    setFeedback('Đang gửi yêu cầu đổi quà...', 'info');

    try {
        await window.FrontsiteRecaptchaV3?.executeForForm(activeForm);

        const response = await fetch(activeForm.action, {
            method: String(activeForm.method || 'POST').toUpperCase(),
            body: new FormData(activeForm),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await responsePayload(response);

        if (! response.ok) {
            throw new Error(messageFromPayload(payload) || 'Không gửi được yêu cầu đổi quà. Vui lòng thử lại.');
        }

        const message = messageFromPayload(payload) || 'Yêu cầu đổi quà đã được ghi nhận.';
        setFeedback(message, 'success');
        await refreshLoyaltyPanels(payload.refresh_url || window.location.href);
        submitLocked = true;
        setSubmitButtonState(false, 'Đã gửi');
    } catch (error) {
        setFeedback(error?.message || 'Không gửi được yêu cầu đổi quà. Vui lòng thử lại.', 'error');
        setSubmitButtonState(false);
    } finally {
        isSubmitting = false;
    }
}

async function responsePayload(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return await response.json();
    }

    return {
        message: (await response.text()).trim(),
    };
}

async function refreshLoyaltyPanels(url) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (! response.ok) {
        return;
    }

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');

    PANEL_SELECTORS.forEach((selector) => {
        const currentPanel = document.querySelector(selector);
        const nextPanel = nextDocument.querySelector(selector);

        if (currentPanel && nextPanel) {
            currentPanel.replaceWith(nextPanel);
        }
    });
}

function setModalContent(form) {
    const giftTitle = String(form.dataset.customerLoyaltyGiftTitle || form.querySelector('[name="gift_name"]')?.value || 'quà tặng').trim();
    const phone = String(form.dataset.customerLoyaltyPhone || form.querySelector('[name="phone"]')?.value || '').trim();
    const title = document.querySelector('[data-customer-loyalty-redemption-title]');
    const message = document.querySelector('[data-customer-loyalty-redemption-message]');

    if (title) {
        title.textContent = `Đổi ${giftTitle}`;
    }

    if (message) {
        message.textContent = phone
            ? `Chúng tôi sẽ liên hệ lại với bạn để xác nhận yêu cầu đổi quà ${giftTitle} cho SĐT ${phone} này.`
            : `Chúng tôi sẽ liên hệ lại với bạn để xác nhận yêu cầu đổi quà ${giftTitle} này.`;
    }
}

function messageFromPayload(payload) {
    if (! payload || typeof payload !== 'object') {
        return '';
    }

    if (typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message.trim();
    }

    const errors = payload.errors;

    if (errors && typeof errors === 'object') {
        const firstError = Object.values(errors).flat().find((value) => typeof value === 'string' && value.trim() !== '');

        if (firstError) {
            return firstError.trim();
        }
    }

    return '';
}

function setFeedback(message = '', type = 'info') {
    const feedback = document.querySelector('[data-customer-loyalty-redemption-feedback]');

    if (! feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.toggle('hidden', message === '');
    feedback.classList.toggle('border-emerald-200', type === 'success');
    feedback.classList.toggle('bg-emerald-50', type === 'success');
    feedback.classList.toggle('text-emerald-800', type === 'success');
    feedback.classList.toggle('border-red-200', type === 'error');
    feedback.classList.toggle('bg-red-50', type === 'error');
    feedback.classList.toggle('text-red-700', type === 'error');
    feedback.classList.toggle('border-slate-200', type === 'info');
    feedback.classList.toggle('bg-slate-50', type === 'info');
    feedback.classList.toggle('text-slate-700', type === 'info');
}

function setSubmitButtonState(isBusy, text = 'Gửi yêu cầu') {
    const button = document.querySelector('[data-customer-loyalty-redemption-submit]');

    if (! (button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = isBusy || submitLocked;
    button.classList.toggle('cursor-wait', isBusy);
    button.classList.toggle('opacity-75', isBusy || submitLocked);
    button.innerHTML = isBusy
        ? '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Đang gửi'
        : `<i class="fa-solid fa-paper-plane" aria-hidden="true"></i> ${text}`;
}

function redemptionModal() {
    return document.querySelector(MODAL_SELECTOR);
}

function openModal(modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => {
        document.querySelector('[data-customer-loyalty-redemption-submit]')?.focus();
    });
}

function closeModal() {
    if (isSubmitting) {
        return;
    }

    const modal = redemptionModal();

    if (! modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('overflow-hidden');
    setFeedback();
    submitLocked = false;
    setSubmitButtonState(false);
    activeForm = null;
}

function isModalOpen() {
    const modal = redemptionModal();

    return modal ? ! modal.classList.contains('hidden') : false;
}
