import Swal from 'sweetalert2';

const FORM_SELECTOR = 'form[data-admin-feedback-form]';
const STATUS_SELECTOR = '[data-admin-status-message]';
const ERROR_SELECTOR = '[data-admin-error-summary]';
const HANDLED_ATTR = 'data-admin-feedback-handled';

let listenersBound = false;
let loadingVisible = false;
let renderScheduled = false;
let submissionPending = false;

initializeAdminFormFeedback();

document.addEventListener('DOMContentLoaded', () => initializeAdminFormFeedback());
document.addEventListener('livewire:navigated', () => scheduleRenderSync(document));
document.addEventListener('livewire:initialized', () => {
    initializeAdminFormFeedback();

    if (window.Livewire?.hook) {
        window.Livewire.hook('morph.updated', () => {
            scheduleRenderSync(document);
        });
    }
});

function initializeAdminFormFeedback(root = document) {
    bindListeners();
    syncStatusToasts(root);
}

function bindListeners() {
    if (listenersBound) {
        return;
    }

    listenersBound = true;

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches(FORM_SELECTOR)) {
            return;
        }

        submissionPending = true;

        showLoadingModal(
            form.dataset.adminLoadingText?.trim() || 'Đang lưu dữ liệu...',
        );
    }, true);
}

function scheduleRenderSync(root = document) {
    if (renderScheduled) {
        return;
    }

    renderScheduled = true;

    queueMicrotask(() => {
        renderScheduled = false;
        syncAfterRender(root);
    });
}

function syncAfterRender(root = document) {
    if (submissionPending) {
        submissionPending = false;
        closeLoadingModal();
        syncValidationAlert(root);
    }

    syncStatusToasts(root);
}

function syncStatusToasts(root = document) {
    queryElements(root, STATUS_SELECTOR).forEach((element) => {
        if (element.getAttribute(HANDLED_ATTR) === 'true') {
            return;
        }

        element.setAttribute(HANDLED_ATTR, 'true');

        const message = (element.dataset.adminStatusMessage || element.textContent || '').trim();

        if (!message) {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: message,
            timer: 2600,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: {
                popup: 'admin-swal-popup admin-swal-toast',
                title: 'admin-swal-title admin-swal-title--toast',
            },
        });
    });
}

function syncValidationAlert(root = document) {
    const element = queryElements(root, ERROR_SELECTOR).find(
        (node) => node.getAttribute(HANDLED_ATTR) !== 'true',
    );

    if (!element) {
        return;
    }

    element.setAttribute(HANDLED_ATTR, 'true');

    const message = (element.dataset.adminErrorSummary || 'Vui lòng kiểm tra lại dữ liệu đã nhập.').trim();
    const errorCount = Number(element.dataset.adminErrorCount || '0');
    const footer = errorCount > 1
        ? `Biểu mẫu hiện có ${errorCount} mục cần xem lại.`
        : 'Biểu mẫu còn một mục cần kiểm tra lại.';

    Swal.fire({
        icon: 'warning',
        title: 'Cần kiểm tra lại biểu mẫu',
        text: message,
        footer,
        confirmButtonText: 'Đã hiểu',
        buttonsStyling: false,
        customClass: {
            popup: 'admin-swal-popup',
            title: 'admin-swal-title',
            htmlContainer: 'admin-swal-text',
            confirmButton: 'admin-swal-confirm',
            footer: 'admin-swal-footer',
        },
    });
}

function showLoadingModal(title) {
    loadingVisible = true;

    Swal.fire({
        title,
        text: 'Vui lòng chờ trong giây lát.',
        allowEscapeKey: false,
        allowOutsideClick: false,
        showConfirmButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'admin-swal-popup',
            title: 'admin-swal-title',
            htmlContainer: 'admin-swal-text',
            loader: 'admin-swal-loader',
        },
        didOpen: () => {
            Swal.showLoading();
        },
    });
}

function closeLoadingModal() {
    if (!loadingVisible) {
        return;
    }

    loadingVisible = false;
    Swal.close();
}

function queryElements(root, selector) {
    if (root instanceof Element || root instanceof Document) {
        return Array.from(root.querySelectorAll(selector));
    }

    return Array.from(document.querySelectorAll(selector));
}
