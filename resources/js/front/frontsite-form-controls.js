import '../../css/frontsite-form-controls.css';
import flatpickr from 'flatpickr';
import { Vietnamese } from 'flatpickr/dist/l10n/vn.js';
import TomSelect from 'tom-select';

const frontsiteSelectSelector = '.frontsite-theme select[data-frontsite-select]';
const frontsiteDatepickerSelector = '.frontsite-theme input[data-frontsite-datepicker]';
const customLongDateFormat = 'viLong';
const defaultFlatpickrLocale = flatpickr.l10ns.default ?? {};
const vietnameseFlatpickrLocale = {
    ...defaultFlatpickrLocale,
    ...Vietnamese,
    months: {
        ...defaultFlatpickrLocale.months,
        ...Vietnamese.months,
    },
    weekdays: {
        ...defaultFlatpickrLocale.weekdays,
        ...Vietnamese.weekdays,
    },
};

function parseJsonAttribute(value = '') {
    if (! value) {
        return null;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return null;
    }
}

function formatVietnameseLongDate(date) {
    return new Intl.DateTimeFormat('vi-VN', {
        day: 'numeric',
        month: 'short',
        weekday: 'short',
        year: 'numeric',
    }).format(date);
}

function selectPlaceholder(select) {
    return select.dataset.frontsiteSelectPlaceholder?.trim()
        || select.querySelector('option[value=""]')?.textContent?.trim()
        || '';
}

function enhanceSelect(select) {
    if (select.tomselect) {
        return select.tomselect;
    }

    const isSearchable = select.dataset.frontsiteSelectSearch !== 'false';
    const maxWidth = select.dataset.frontsiteSelectMaxWidth?.trim() || '';
    const placeholder = selectPlaceholder(select);
    const variant = select.dataset.frontsiteSelectVariant?.trim() || 'panel';

    return new TomSelect(select, {
        allowEmptyOption: true,
        closeAfterSelect: true,
        copyClassesToDropdown: false,
        create: false,
        dropdownParent: 'body',
        hidePlaceholder: false,
        maxItems: 1,
        placeholder,
        render: {
            no_results(data, escape) {
                return `<div class="px-3 py-2 text-sm text-slate-500">Không tìm thấy kết quả cho "${escape(data.input)}".</div>`;
            },
        },
        searchField: isSearchable ? ['text'] : [],
        onInitialize() {
            this.wrapper.classList.add('frontsite-select', `frontsite-select--${variant}`);
            this.dropdown.classList.add('frontsite-select-dropdown', `frontsite-select-dropdown--${variant}`);

            if (maxWidth) {
                this.wrapper.style.setProperty('--frontsite-select-max-width', maxWidth);
            }

            if (! isSearchable) {
                this.wrapper.classList.add('frontsite-select--no-search');
                this.control_input?.setAttribute('readonly', 'readonly');
            }

            if (select.disabled) {
                this.wrapper.classList.add('is-disabled');
            }

            if (select.classList.contains('is-invalid') || select.getAttribute('aria-invalid') === 'true') {
                this.wrapper.classList.add('is-invalid');
            }
        },
    });
}

function enhanceDatepicker(input) {
    if (input._flatpickr) {
        return input._flatpickr;
    }

    const appendTarget = input.dataset.frontsiteDatepickerAppendToBody === 'false'
        ? undefined
        : document.body;
    const enabledDates = parseJsonAttribute(input.dataset.frontsiteDatepickerEnabled ?? '');
    const maxWidth = input.dataset.frontsiteDatepickerMaxWidth?.trim() || '';
    const minDate = input.dataset.frontsiteDatepickerMinDate?.trim() || undefined;
    const maxDate = input.dataset.frontsiteDatepickerMaxDate?.trim() || undefined;
    const placeholder = input.dataset.frontsiteDatepickerPlaceholder?.trim() || 'Chọn ngày';
    const variant = input.dataset.frontsiteDatepickerVariant?.trim() || 'panel';
    const datepickerOptions = {
        allowInput: false,
        altFormat: customLongDateFormat,
        altInput: true,
        clickOpens: true,
        dateFormat: 'Y-m-d',
        disableMobile: true,
        locale: vietnameseFlatpickrLocale,
        formatDate(date, format, locale) {
            if (format === customLongDateFormat) {
                return formatVietnameseLongDate(date);
            }

            return flatpickr.formatDate(date, format, locale);
        },
        onReady(selectedDates, dateStr, calendar) {
            const visibleInput = calendar.altInput || calendar.input;

            visibleInput.classList.add('frontsite-datepicker-input', `frontsite-datepicker-input--${variant}`);
            visibleInput.placeholder = placeholder;
            visibleInput.readOnly = true;
            visibleInput.style.cursor = 'pointer';

            if (maxWidth) {
                visibleInput.style.setProperty('--frontsite-datepicker-max-width', maxWidth);
            }

            calendar.calendarContainer.classList.add('frontsite-calendar', `frontsite-calendar--${variant}`);

            if (input.classList.contains('is-invalid') || input.getAttribute('aria-invalid') === 'true') {
                visibleInput.classList.add('is-invalid');
            }
        },
    };

    if (appendTarget) {
        datepickerOptions.appendTo = appendTarget;
    }

    if (Array.isArray(enabledDates) && enabledDates.length) {
        datepickerOptions.enable = enabledDates;
    }

    if (minDate) {
        datepickerOptions.minDate = minDate;
    }

    if (maxDate) {
        datepickerOptions.maxDate = maxDate;
    }

    const instance = flatpickr(input, datepickerOptions);

    return instance;
}

export function initFrontsiteFormControls(root = document) {
    root.querySelectorAll(frontsiteSelectSelector).forEach((select) => {
        enhanceSelect(select);
    });

    root.querySelectorAll(frontsiteDatepickerSelector).forEach((input) => {
        enhanceDatepicker(input);
    });
}
