const FORM_SELECTOR = '.frontsite-theme form[data-frontsite-ajax-form]';
const FEEDBACK_SELECTOR = '[data-frontsite-form-feedback]';
const FEEDBACK_MESSAGE_SELECTOR = '[data-frontsite-form-feedback-message]';
const FEEDBACK_LIST_SELECTOR = '[data-frontsite-form-feedback-list]';
const FIELD_ERROR_SELECTOR = '[data-frontsite-field-error]';
const INVALID_CLASS = 'is-invalid';
const FEEDBACK_SUCCESS_CLASS = 'is-success';
const FEEDBACK_ERROR_CLASS = 'is-error';

let listenersBound = false;

export function initFrontsiteAjaxForms() {
    bindListeners();
}

function bindListeners() {
    if (listenersBound) {
        return;
    }

    listenersBound = true;

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement) || ! form.matches(FORM_SELECTOR)) {
            return;
        }

        if (form.dataset.frontsiteFormSubmitting === 'true') {
            event.preventDefault();

            return;
        }

        event.preventDefault();
        submitForm(form);
    });
}

async function submitForm(form) {
    clearFormState(form);
    setSubmittingState(form, true);

    try {
        const response = await fetch(form.action || window.location.href, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (response.ok) {
            handleSuccess(form, payload);

            return;
        }

        if (response.status === 422 && payload?.errors) {
            handleValidationError(form, payload.errors);

            return;
        }

        handleRequestFailure(form, payload?.message);
    } catch (error) {
        handleRequestFailure(form);
    } finally {
        setSubmittingState(form, false);
    }
}

function handleSuccess(form, payload = {}) {
    resetFormFields(form);
    showFeedback(form, {
        message: payload?.message || form.dataset.frontsiteSuccessMessage || 'Yêu cầu của bạn đã được gửi thành công.',
        type: 'success',
    });

    form.dispatchEvent(new CustomEvent('frontsite:ajax-form-success', {
        bubbles: true,
        detail: {
            form,
            payload,
        },
    }));
}

function handleValidationError(form, errors) {
    const messages = flattenErrors(errors);

    showFeedback(form, {
        list: messages.slice(0, 5),
        message: 'Vui lòng kiểm tra lại các thông tin đã nhập.',
        type: 'error',
    });

    let firstInvalidTarget = null;

    Object.entries(errors).forEach(([fieldName, fieldMessages]) => {
        const message = Array.isArray(fieldMessages) ? fieldMessages[0] : fieldMessages;
        const target = setFieldError(form, fieldName, message);

        if (! firstInvalidTarget && target instanceof HTMLElement) {
            firstInvalidTarget = target;
        }
    });

    if (firstInvalidTarget instanceof HTMLElement) {
        focusFieldTarget(firstInvalidTarget);
    }

    form.dispatchEvent(new CustomEvent('frontsite:ajax-form-error', {
        bubbles: true,
        detail: {
            errors,
            form,
            type: 'validation',
        },
    }));
}

function handleRequestFailure(form, message = '') {
    showFeedback(form, {
        message: message || 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau hoặc gọi trực tiếp hotline.',
        type: 'error',
    });

    form.dispatchEvent(new CustomEvent('frontsite:ajax-form-error', {
        bubbles: true,
        detail: {
            form,
            type: 'request',
        },
    }));
}

function clearFormState(form) {
    clearFeedback(form);

    form.querySelectorAll(FIELD_ERROR_SELECTOR).forEach((element) => {
        if (! (element instanceof HTMLElement)) {
            return;
        }

        element.textContent = '';
        element.classList.add('hidden');
    });

    Array.from(form.elements).forEach((field) => {
        if (! isFormField(field)) {
            return;
        }

        setFieldValidity(field, false);
    });
}

function clearFeedback(form) {
    const feedback = form.querySelector(FEEDBACK_SELECTOR);

    if (! (feedback instanceof HTMLElement)) {
        return;
    }

    feedback.classList.add('hidden');
    feedback.classList.remove(FEEDBACK_SUCCESS_CLASS, FEEDBACK_ERROR_CLASS);

    const message = feedback.querySelector(FEEDBACK_MESSAGE_SELECTOR);

    if (message instanceof HTMLElement) {
        message.textContent = '';
    }

    const list = feedback.querySelector(FEEDBACK_LIST_SELECTOR);

    if (list instanceof HTMLElement) {
        list.innerHTML = '';
        list.classList.add('hidden');
    }
}

function showFeedback(form, { message = '', list = [], type = 'success' } = {}) {
    const feedback = form.querySelector(FEEDBACK_SELECTOR);

    if (! (feedback instanceof HTMLElement)) {
        return;
    }

    feedback.classList.remove('hidden', FEEDBACK_SUCCESS_CLASS, FEEDBACK_ERROR_CLASS);
    feedback.classList.add(type === 'success' ? FEEDBACK_SUCCESS_CLASS : FEEDBACK_ERROR_CLASS);

    const messageTarget = feedback.querySelector(FEEDBACK_MESSAGE_SELECTOR);

    if (messageTarget instanceof HTMLElement) {
        messageTarget.textContent = message;
    }

    const listTarget = feedback.querySelector(FEEDBACK_LIST_SELECTOR);

    if (listTarget instanceof HTMLElement) {
        listTarget.innerHTML = '';

        if (list.length > 0) {
            listTarget.classList.remove('hidden');

            list.forEach((item) => {
                const listItem = document.createElement('li');
                listItem.textContent = item;
                listTarget.appendChild(listItem);
            });
        } else {
            listTarget.classList.add('hidden');
        }
    }

    if (feedback.tabIndex < 0) {
        feedback.tabIndex = -1;
    }

    feedback.focus();
}

function setFieldError(form, fieldName, message) {
    const field = findField(form, fieldName);

    if (! field) {
        return null;
    }

    setFieldValidity(field, true);

    const escapedFieldName = escapeAttributeValue(fieldName);
    const errorTarget = form.querySelector(`${FIELD_ERROR_SELECTOR}[data-frontsite-field-error="${escapedFieldName}"]`);

    if (errorTarget instanceof HTMLElement) {
        errorTarget.textContent = message;
        errorTarget.classList.remove('hidden');
    }

    return resolveFieldTarget(field);
}

function setFieldValidity(field, isInvalid) {
    field.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
    field.classList.toggle(INVALID_CLASS, isInvalid);

    if (field.tomselect?.wrapper instanceof HTMLElement) {
        field.tomselect.wrapper.classList.toggle(INVALID_CLASS, isInvalid);
    }

    if (field._flatpickr?.altInput instanceof HTMLElement) {
        field._flatpickr.altInput.classList.toggle(INVALID_CLASS, isInvalid);
        field._flatpickr.altInput.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
    }
}

function setSubmittingState(form, isSubmitting) {
    form.dataset.frontsiteFormSubmitting = isSubmitting ? 'true' : 'false';
    form.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');

    form.querySelectorAll('[type="submit"]').forEach((button) => {
        if (! (button instanceof HTMLButtonElement) && ! (button instanceof HTMLInputElement)) {
            return;
        }

        button.disabled = isSubmitting;
        button.classList.toggle('opacity-75', isSubmitting);
        button.classList.toggle('cursor-wait', isSubmitting);
    });
}

function resetFormFields(form) {
    Array.from(form.elements).forEach((field) => {
        if (! isFormField(field) || field.type === 'hidden') {
            return;
        }

        if (field instanceof HTMLSelectElement) {
            const nextValue = defaultSelectValue(field);

            if (field.tomselect) {
                field.tomselect.setValue(nextValue, true);
                field.tomselect.sync();
            } else {
                field.value = nextValue;
            }

            return;
        }

        if (field instanceof HTMLTextAreaElement) {
            field.value = '';

            return;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;

            return;
        }

        if (field._flatpickr) {
            field._flatpickr.clear();

            return;
        }

        field.value = '';
    });
}

function flattenErrors(errors = {}) {
    return Object.values(errors)
        .flatMap((value) => Array.isArray(value) ? value : [value])
        .filter((value, index, values) => Boolean(value) && values.indexOf(value) === index);
}

function findField(form, fieldName) {
    const selector = `[name="${escapeAttributeValue(fieldName)}"]`;

    return form.querySelector(selector);
}

function resolveFieldTarget(field) {
    if (field._flatpickr?.altInput instanceof HTMLElement) {
        return field._flatpickr.altInput;
    }

    if (field.tomselect?.focus && field.tomselect.wrapper instanceof HTMLElement) {
        return field.tomselect.wrapper;
    }

    return field;
}

function focusFieldTarget(target) {
    if (target.tomselect?.focus) {
        target.tomselect.focus();

        return;
    }

    if (target instanceof HTMLElement && target.classList.contains('ts-wrapper')) {
        const controlInput = target.querySelector('input');

        if (controlInput instanceof HTMLElement) {
            controlInput.focus();

            return;
        }
    }

    target.focus();
}

function isFormField(field) {
    return field instanceof HTMLInputElement
        || field instanceof HTMLSelectElement
        || field instanceof HTMLTextAreaElement;
}

function escapeAttributeValue(value) {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
    }

    return `${value}`.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function defaultSelectValue(field) {
    const selectedOption = Array.from(field.options).find((option) => option.defaultSelected);

    if (selectedOption) {
        return selectedOption.value;
    }

    return field.options[0]?.value || '';
}

window.FrontsiteAjaxForms = {
    clearFormState,
};
