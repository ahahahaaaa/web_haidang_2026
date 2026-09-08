import TomSelect from 'tom-select';

const MULTISELECT_SELECTOR = 'select[multiple][data-admin-multiselect]';
let multiselectObserver = null;
let livewireHooksBound = false;
let bootScheduled = false;

bootAdminMultiselects();
ensureMultiselectObserver();
bindLivewireHooks();

document.addEventListener('DOMContentLoaded', () => {
    bootAdminMultiselects();
    ensureMultiselectObserver();
    bindLivewireHooks();
});
document.addEventListener('livewire:navigated', () => bootAdminMultiselects());
document.addEventListener('livewire:initialized', () => {
    bootAdminMultiselects();
    ensureMultiselectObserver();
    bindLivewireHooks();
});

export function initAdminMultiselects(root = document) {
    getMultiselectRoots(root).forEach((select) => {
        enhanceMultiselect(select);
    });
}

function bootAdminMultiselects(root = document) {
    initAdminMultiselects(root);
}

function bindLivewireHooks() {
    if (livewireHooksBound || !window.Livewire?.hook) {
        return;
    }

    livewireHooksBound = true;

    window.Livewire.hook('morph.updating', ({ el }) => {
        destroyAdminMultiselects(el);
    });

    window.Livewire.hook('morph.updated', ({ el }) => {
        scheduleBootAdminMultiselects(el);
    });
}

function destroyAdminMultiselects(root = document) {
    getMultiselectRoots(root).forEach((select) => {
        select.tomselect?.destroy();
    });
}

function ensureMultiselectObserver() {
    if (multiselectObserver || typeof MutationObserver === 'undefined') {
        return;
    }

    const root = document.body;

    if (!root) {
        return;
    }

    multiselectObserver = new MutationObserver((mutations) => {
        const insertedRoots = [];

        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches?.(MULTISELECT_SELECTOR) || node.querySelector?.(MULTISELECT_SELECTOR)) {
                    insertedRoots.push(node);
                }
            });
        });

        if (!insertedRoots.length) {
            return;
        }

        insertedRoots.forEach((node) => scheduleBootAdminMultiselects(node));
    });

    multiselectObserver.observe(root, {
        childList: true,
        subtree: true,
    });
}

function scheduleBootAdminMultiselects(root = document) {
    if (bootScheduled) {
        return;
    }

    bootScheduled = true;

    queueMicrotask(() => {
        bootScheduled = false;
        bootAdminMultiselects(root instanceof Element || root instanceof Document ? root : document);
    });
}

function enhanceMultiselect(select) {
    if (select.tomselect) {
        syncMultiselectState(select.tomselect, select);

        return select.tomselect;
    }

    const isSearchable = select.dataset.adminMultiselectSearch !== 'false';
    const placeholder = resolvePlaceholder(select);

    return new TomSelect(select, {
        allowEmptyOption: true,
        closeAfterSelect: false,
        copyClassesToDropdown: false,
        create: false,
        dropdownParent: 'body',
        hidePlaceholder: false,
        maxItems: null,
        persist: false,
        placeholder,
        plugins: ['remove_button'],
        render: {
            no_results(data, escape) {
                if (!data.input) {
                    return '<div class="admin-multiselect-empty">Không tìm thấy kết quả.</div>';
                }

                return `<div class="admin-multiselect-empty">Không tìm thấy kết quả cho "${escape(data.input)}".</div>`;
            },
        },
        searchField: isSearchable ? ['text'] : [],
        onInitialize() {
            this.wrapper.classList.add('admin-multiselect');
            this.dropdown.classList.add('admin-multiselect-dropdown');

            if (!isSearchable) {
                this.wrapper.classList.add('admin-multiselect--no-search');
                this.control_input?.setAttribute('readonly', 'readonly');
            }

            syncMultiselectState(this, select);
        },
    });
}

function syncMultiselectState(instance, select) {
    instance.wrapper.classList.toggle('is-disabled', Boolean(select.disabled));
    instance.wrapper.classList.toggle(
        'is-invalid',
        select.classList.contains('is-invalid') || select.getAttribute('aria-invalid') === 'true',
    );
}

function resolvePlaceholder(select) {
    return select.dataset.adminMultiselectPlaceholder?.trim() || '';
}

function getMultiselectRoots(root = document) {
    if (root instanceof HTMLSelectElement) {
        return root.matches(MULTISELECT_SELECTOR) ? [root] : [];
    }

    if (root instanceof Element || root instanceof Document) {
        const matchedRoot = root instanceof Element && root.matches(MULTISELECT_SELECTOR)
            ? [root]
            : [];

        return [
            ...matchedRoot,
            ...root.querySelectorAll(MULTISELECT_SELECTOR),
        ];
    }

    return Array.from(document.querySelectorAll(MULTISELECT_SELECTOR));
}
