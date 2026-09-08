const ROOT_SELECTOR = '[data-voucher-countdown]';

const UNITS = [
    ['days', 24 * 60 * 60 * 1000],
    ['hours', 60 * 60 * 1000],
    ['minutes', 60 * 1000],
    ['seconds', 1000],
];

const intervals = new WeakMap();

function twoDigits(value) {
    return String(Math.max(0, value)).padStart(2, '0');
}

function setUnit(root, unit, value) {
    const target = root.querySelector(`[data-voucher-countdown-${unit}]`);

    if (target) {
        target.textContent = twoDigits(value);
    }
}

function renderCountdown(root, targetTime) {
    const remaining = targetTime - Date.now();

    if (remaining <= 0) {
        UNITS.forEach(([unit]) => setUnit(root, unit, 0));
        root.classList.add('is-expired');

        const status = root.querySelector('[data-voucher-countdown-status]');
        const expiredText = root.dataset.voucherCountdownExpired || 'Chương trình voucher đã kết thúc';

        if (status) {
            status.textContent = expiredText;
        }

        const interval = intervals.get(root);

        if (interval) {
            window.clearInterval(interval);
            intervals.delete(root);
        }

        return;
    }

    let cursor = remaining;

    UNITS.forEach(([unit, duration]) => {
        const value = Math.floor(cursor / duration);
        cursor -= value * duration;
        setUnit(root, unit, value);
    });
}

export function initVoucherCountdowns(root = document) {
    root.querySelectorAll(ROOT_SELECTOR).forEach((element) => {
        if (! (element instanceof HTMLElement) || intervals.has(element)) {
            return;
        }

        const target = Date.parse(element.dataset.voucherCountdownTarget || '');

        if (Number.isNaN(target)) {
            return;
        }

        renderCountdown(element, target);
        intervals.set(element, window.setInterval(() => renderCountdown(element, target), 1000));
    });
}
