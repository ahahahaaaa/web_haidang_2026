const FORM_SELECTOR = '.frontsite-theme form[data-frontsite-recaptcha-form]';
const AJAX_FORM_SELECTOR = 'form[data-frontsite-ajax-form]';
const CUSTOMER_LOYALTY_FORM_SELECTOR = 'form[data-customer-loyalty-redemption-form]';
const TOKEN_FIELD_NAME = 'g-recaptcha-response';

let listenersBound = false;

export function initFrontsiteRecaptchaV3() {
    bindNonAjaxFormListener();

    window.FrontsiteRecaptchaV3 = {
        executeForForm,
        isEnabled,
    };
}

async function executeForForm(form) {
    if (! isEnabled()) {
        return true;
    }

    const grecaptcha = await waitForGrecaptcha();
    const config = window.HaidangRecaptchaV3 || {};
    const siteKey = String(config.siteKey || '').trim();

    if (! siteKey) {
        throw new Error('Chưa cấu hình Site key Google reCAPTCHA v3.');
    }

    const action = normalizeAction(form.dataset.frontsiteRecaptchaAction || config.defaultAction || 'frontsite_form');
    const token = await new Promise((resolve, reject) => {
        grecaptcha.ready(() => {
            grecaptcha.execute(siteKey, { action })
                .then(resolve)
                .catch(reject);
        });
    });

    const tokenValue = String(token || '').trim();

    if (! tokenValue) {
        throw new Error('Không lấy được mã xác minh reCAPTCHA.');
    }

    tokenField(form).value = tokenValue;

    return true;
}

function bindNonAjaxFormListener() {
    if (listenersBound) {
        return;
    }

    listenersBound = true;

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement)
            || ! form.matches(FORM_SELECTOR)
            || form.matches(AJAX_FORM_SELECTOR)
            || form.matches(CUSTOMER_LOYALTY_FORM_SELECTOR)) {
            return;
        }

        if (form.dataset.frontsiteRecaptchaSubmitting === 'true') {
            return;
        }

        const confirmMessage = String(form.dataset.frontsiteConfirmMessage || '').trim();

        if (confirmMessage && ! window.confirm(confirmMessage)) {
            event.preventDefault();

            return;
        }

        if (! isEnabled()) {
            return;
        }

        event.preventDefault();
        setSubmittingState(form, true);

        executeForForm(form)
            .then(() => {
                form.dataset.frontsiteRecaptchaSubmitting = 'true';
                HTMLFormElement.prototype.submit.call(form);
            })
            .catch((error) => {
                setSubmittingState(form, false);
                window.alert(error?.message || 'Không thể xác minh reCAPTCHA. Vui lòng thử lại.');
            });
    }, true);
}

function isEnabled() {
    const config = window.HaidangRecaptchaV3 || {};

    return config.enabled === true && String(config.siteKey || '').trim() !== '';
}

function waitForGrecaptcha() {
    if (typeof window.grecaptcha !== 'undefined') {
        return Promise.resolve(window.grecaptcha);
    }

    return new Promise((resolve, reject) => {
        let attempts = 0;
        const timer = window.setInterval(() => {
            attempts += 1;

            if (typeof window.grecaptcha !== 'undefined') {
                window.clearInterval(timer);
                resolve(window.grecaptcha);

                return;
            }

            if (attempts >= 30) {
                window.clearInterval(timer);
                reject(new Error('Google reCAPTCHA chưa sẵn sàng. Vui lòng thử lại sau.'));
            }
        }, 150);
    });
}

function tokenField(form) {
    let field = form.querySelector(`input[name="${TOKEN_FIELD_NAME}"]`);

    if (field instanceof HTMLInputElement) {
        return field;
    }

    field = document.createElement('input');
    field.type = 'hidden';
    field.name = TOKEN_FIELD_NAME;
    field.setAttribute('data-google-recaptcha-v3-token', '');
    form.appendChild(field);

    return field;
}

function normalizeAction(value) {
    const action = String(value || 'frontsite_form').replace(/[^A-Za-z0-9_/-]/g, '_');

    return action || 'frontsite_form';
}

function setSubmittingState(form, isSubmitting) {
    form.querySelectorAll('[type="submit"]').forEach((button) => {
        if (! (button instanceof HTMLButtonElement) && ! (button instanceof HTMLInputElement)) {
            return;
        }

        button.disabled = isSubmitting;
        button.classList.toggle('opacity-75', isSubmitting);
        button.classList.toggle('cursor-wait', isSubmitting);
    });
}
