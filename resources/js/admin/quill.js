import Quill from 'quill';

const EDITOR_SELECTOR = '[data-admin-quill]';
const EMPTY_RICH_VALUE = '<p><br></p>';
const DEFAULT_TABLE_COLUMNS = 3;
const DEFAULT_TABLE_ROWS = 3;
const MAX_TABLE_COLUMNS = 8;
const MAX_TABLE_ROWS = 12;
const editors = new WeakMap();

let mediaBrowser = null;
let editorObserver = null;
let bootScheduled = false;

bootEditors();
ensureEditorObserver();

document.addEventListener('DOMContentLoaded', () => bootEditors());
document.addEventListener('livewire:navigated', () => bootEditors());
document.addEventListener('livewire:initialized', () => {
    bootEditors();
    ensureEditorObserver();

    if (window.Livewire?.hook) {
        window.Livewire.hook('morph.updated', ({ el }) => {
            scheduleBootEditors(el);
        });
    }
});

function bootEditors(root = document) {
    getEditorRoots(root).forEach(initializeEditor);
    ensureMediaBrowser();
}

function ensureMediaBrowser() {
    const modal = document.getElementById('admin-media-browser');

    if (!modal) {
        mediaBrowser = null;

        return null;
    }

    if (!mediaBrowser || !mediaBrowser.matches(modal)) {
        mediaBrowser = createMediaBrowser(modal);
    }

    return mediaBrowser;
}

function ensureEditorObserver() {
    if (editorObserver || typeof MutationObserver === 'undefined') {
        return;
    }

    const root = document.body;

    if (!root) {
        return;
    }

    editorObserver = new MutationObserver((mutations) => {
        const insertedRoots = [];

        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches?.(EDITOR_SELECTOR) || node.querySelector?.(EDITOR_SELECTOR)) {
                    insertedRoots.push(node);
                }
            });
        });

        if (!insertedRoots.length) {
            return;
        }

        insertedRoots.forEach((node) => scheduleBootEditors(node));
    });

    editorObserver.observe(root, {
        childList: true,
        subtree: true,
    });
}

function scheduleBootEditors(root = document) {
    if (bootScheduled) {
        return;
    }

    bootScheduled = true;

    queueMicrotask(() => {
        bootScheduled = false;
        bootEditors(document);
    });
}

function createMediaBrowser(modal) {
    if (!modal) {
        return null;
    }

    const config = {
        apiUrl: getMetaContent('admin-media-browser-url'),
        manageUrl: getMetaContent('admin-media-manager-url'),
        uploadUrl: getMetaContent('admin-media-upload-url'),
        csrfToken: getMetaContent('csrf-token'),
    };

    const elements = {
        alt: modal.querySelector('[data-media-alt]'),
        backdrop: modal.querySelector('[data-media-backdrop]'),
        closeButtons: modal.querySelectorAll('[data-media-close]'),
        collection: modal.querySelector('[data-media-collection]'),
        empty: modal.querySelector('[data-media-empty]'),
        editorFields: modal.querySelector('[data-media-editor-fields]'),
        grid: modal.querySelector('[data-media-grid]'),
        imageClass: modal.querySelector('[data-media-class]'),
        insertButton: modal.querySelector('[data-media-insert]'),
        insertText: modal.querySelector('[data-media-insert-text]'),
        manageLink: modal.querySelector('[data-media-manage-link]'),
        model: modal.querySelector('[data-media-model]'),
        next: modal.querySelector('[data-media-next]'),
        pagination: modal.querySelector('[data-media-pagination]'),
        panel: modal.querySelector('[data-media-panel]'),
        previewImage: modal.querySelector('[data-media-preview-image]'),
        previewMeta: modal.querySelector('[data-media-preview-meta]'),
        previewName: modal.querySelector('[data-media-preview-name]'),
        prev: modal.querySelector('[data-media-prev]'),
        resultsSummary: modal.querySelector('[data-media-results-summary]'),
        search: modal.querySelector('[data-media-search]'),
        style: modal.querySelector('[data-media-style]'),
        uploadAlt: modal.querySelector('[data-media-upload-alt]'),
        uploadButton: modal.querySelector('[data-media-upload]'),
        uploadFile: modal.querySelector('[data-media-upload-file]'),
        uploadLabel: modal.querySelector('[data-media-upload-label]'),
        uploadName: modal.querySelector('[data-media-upload-name]'),
        uploadStatus: modal.querySelector('[data-media-upload-status]'),
    };

    if (elements.manageLink && config.manageUrl) {
        elements.manageLink.href = config.manageUrl;
    }

    const state = {
        activeWrapper: null,
        collection: '',
        items: [],
        lastRange: null,
        model: '',
        page: 1,
        picker: null,
        query: '',
        searchTimer: null,
        selected: null,
        totalPages: 1,
        uploading: false,
    };

    elements.backdrop?.addEventListener('click', close);
    elements.closeButtons.forEach((button) => button.addEventListener('click', close));

    elements.search?.addEventListener('input', (event) => {
        window.clearTimeout(state.searchTimer);

        state.searchTimer = window.setTimeout(() => {
            state.query = event.target.value.trim();
            state.page = 1;
            fetchImages();
        }, 250);
    });

    elements.collection?.addEventListener('change', (event) => {
        state.collection = event.target.value;
        state.page = 1;
        fetchImages();
    });

    elements.model?.addEventListener('change', (event) => {
        state.model = event.target.value;
        state.page = 1;
        fetchImages();
    });

    elements.prev?.addEventListener('click', () => {
        if (state.page <= 1) {
            return;
        }

        state.page -= 1;
        fetchImages();
    });

    elements.next?.addEventListener('click', () => {
        if (state.page >= state.totalPages) {
            return;
        }

        state.page += 1;
        fetchImages();
    });

    elements.uploadFile?.addEventListener('change', () => {
        const file = elements.uploadFile.files?.[0];

        if (!file) {
            return;
        }

        const fallbackName = file.name.replace(/\.[^.]+$/, '');

        if (elements.uploadName && !elements.uploadName.value.trim()) {
            elements.uploadName.value = fallbackName;
        }

        if (elements.uploadAlt && !elements.uploadAlt.value.trim()) {
            elements.uploadAlt.value = fallbackName;
        }

        setUploadStatus();
    });

    elements.uploadButton?.addEventListener('click', () => {
        void uploadImage();
    });

    elements.insertButton?.addEventListener('click', () => {
        if (!state.selected) {
            return;
        }

        if (state.picker) {
            chooseForPicker();

            return;
        }

        if (!state.activeWrapper) {
            return;
        }

        const instance = editors.get(state.activeWrapper);

        if (!instance) {
            return;
        }

        const alt = elements.alt?.value.trim() || state.selected.alt || state.selected.name;
        const imageClass = elements.imageClass?.value.trim() || '';
        const style = elements.style?.value.trim() || '';
        const html = buildImageHtml({
            alt,
            className: imageClass,
            mediaId: state.selected.id,
            src: state.selected.url,
            style,
        });
        const insertAt = state.lastRange?.index ?? instance.quill.getLength();

        instance.quill.focus();
        instance.quill.clipboard.dangerouslyPasteHTML(insertAt, html, 'user');
        instance.quill.setSelection(insertAt + 1, 0, 'silent');
        syncSourceValue(state.activeWrapper);
        close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    return {
        matches(currentModal) {
            return modal.isConnected && modal === currentModal;
        },
        open,
        openPicker,
    };

    async function fetchImages() {
        if (!config.apiUrl) {
            return;
        }

        elements.resultsSummary.textContent = 'Đang tải danh sách ảnh...';
        elements.grid.innerHTML = '';
        elements.empty.classList.remove('hidden');
        elements.panel.classList.add('hidden');

        const url = new URL(config.apiUrl, window.location.origin);
        url.searchParams.set('page', String(state.page));

        if (state.query) {
            url.searchParams.set('q', state.query);
        }

        if (state.collection) {
            url.searchParams.set('collection', state.collection);
        }

        if (state.model) {
            url.searchParams.set('model', state.model);
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load media.');
            }

            const payload = await response.json();
            state.items = payload.data || [];
            state.totalPages = payload.meta?.last_page || 1;

            syncSelectOptions(elements.collection, payload.filters?.collections || [], state.collection, 'Tất cả collection');
            syncSelectOptions(elements.model, payload.filters?.models || [], state.model, 'Tất cả đối tượng');
            renderGrid();
            renderPagination(payload.meta);
        } catch (_error) {
            elements.resultsSummary.textContent = 'Không thể tải thư viện media lúc này.';
            elements.grid.innerHTML = '<div class="admin-media-empty-state">Đã xảy ra lỗi khi tải ảnh. Vui lòng thử lại.</div>';
        }
    }

    function close() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        state.activeWrapper = null;
        state.lastRange = null;
        state.picker = null;
        state.selected = null;
        resetUploadForm();
        setUploadStatus();
        syncModalMode();
        renderSelection();
    }

    function open(wrapper) {
        state.activeWrapper = wrapper;
        state.page = 1;
        state.picker = null;
        state.selected = null;

        const instance = editors.get(wrapper);
        state.lastRange = instance?.quill.getSelection(true) || {
            index: instance?.quill.getLength() || 0,
            length: 0,
        };

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        syncModalMode();
        syncUploadButton();
        setUploadStatus();
        fetchImages();
    }

    function openPicker(options = {}) {
        state.activeWrapper = null;
        state.lastRange = null;
        state.page = 1;
        state.picker = {
            buttonLabel: options.buttonLabel || 'Chọn ảnh này',
            componentId: options.componentId || '',
            context: options.context || '',
            method: options.method || 'selectLibraryMedia',
            target: options.target || '',
            altTarget: options.altTarget || '',
        };
        state.selected = null;

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        syncModalMode();
        syncUploadButton();
        setUploadStatus();
        fetchImages();
    }

    function renderGrid() {
        elements.grid.innerHTML = '';

        if (!state.items.length) {
            elements.resultsSummary.textContent = 'Không tìm thấy ảnh nào khớp bộ lọc hiện tại.';
            elements.grid.innerHTML = '<div class="admin-media-empty-state">Không có ảnh phù hợp. Hãy thử đổi bộ lọc hoặc tải ảnh mới trong trang Media.</div>';

            return;
        }

        elements.resultsSummary.textContent = `Tìm thấy ${state.items.length} ảnh trên trang hiện tại. Chọn một ảnh để xem chi tiết.`;

        state.items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `admin-media-card${state.selected?.id === item.id ? ' is-active' : ''}`;
            button.innerHTML = `
                <div class="admin-media-card__preview">
                    <img src="${escapeAttribute(item.url)}" alt="${escapeAttribute(item.alt || item.name)}">
                </div>
                <div class="admin-media-card__body">
                    <p class="admin-media-card__title">${escapeText(item.name)}</p>
                    <p class="admin-media-card__meta">${escapeText(item.model_label)} · ${escapeText(item.collection_name)}</p>
                    <p class="admin-media-card__meta">${escapeText(item.size)}${item.created_at ? ` · ${escapeText(item.created_at)}` : ''}</p>
                </div>
            `;
            button.addEventListener('click', () => {
                state.selected = item;
                renderSelection();
                renderGrid();
            });

            elements.grid.appendChild(button);
        });
    }

    function renderPagination(meta = {}) {
        const currentPage = meta.current_page || 1;
        const total = meta.total || state.items.length;

        elements.pagination.textContent = `Trang ${currentPage}/${state.totalPages} · Tổng ${total} ảnh`;
        elements.prev.disabled = currentPage <= 1;
        elements.next.disabled = currentPage >= state.totalPages;
    }

    function renderSelection() {
        if (!state.selected) {
            elements.empty.classList.remove('hidden');
            elements.panel.classList.add('hidden');
            elements.previewImage.removeAttribute('src');
            elements.previewName.textContent = '';
            elements.previewMeta.textContent = '';
            elements.alt.value = '';
            elements.imageClass.value = '';
            elements.style.value = '';

            return;
        }

        elements.empty.classList.add('hidden');
        elements.panel.classList.remove('hidden');
        elements.previewImage.src = state.selected.url;
        elements.previewImage.alt = state.selected.alt || state.selected.name;
        elements.previewName.textContent = state.selected.name;
        elements.previewMeta.textContent = `${state.selected.model_label} · ${state.selected.collection_name} · ${state.selected.size}`;
        elements.alt.value = state.selected.alt || state.selected.name || '';
        elements.imageClass.value = '';
        elements.style.value = 'max-width: 100%; height: auto;';
    }

    function resetUploadForm() {
        if (elements.uploadFile) {
            elements.uploadFile.value = '';
        }

        if (elements.uploadName) {
            elements.uploadName.value = '';
        }

        if (elements.uploadAlt) {
            elements.uploadAlt.value = '';
        }
    }

    function chooseForPicker() {
        const componentId = state.picker?.componentId;
        const method = state.picker?.method;

        if (!componentId || !method) {
            return;
        }

        const component = window.Livewire?.find(componentId);

        if (!component) {
            return;
        }

        const alt = elements.alt?.value.trim() || state.selected.alt || state.selected.name;
        const context = state.picker?.context || '';
        const target = state.picker?.target || '';
        const altTarget = state.picker?.altTarget || '';

        if (target) {
            component.call(method, target, state.selected.id, alt, altTarget);
            close();

            return;
        }

        if (context) {
            component.call(method, context, state.selected.id, alt);
        } else {
            component.call(method, state.selected.id, alt);
        }

        close();
    }

    function setUploadStatus(message = '', tone = 'info') {
        if (!elements.uploadStatus) {
            return;
        }

        elements.uploadStatus.classList.remove('hidden', 'admin-media-status--success', 'admin-media-status--error');

        if (!message) {
            elements.uploadStatus.textContent = '';
            elements.uploadStatus.classList.add('hidden');

            return;
        }

        if (tone === 'success') {
            elements.uploadStatus.classList.add('admin-media-status--success');
        }

        if (tone === 'error') {
            elements.uploadStatus.classList.add('admin-media-status--error');
        }

        elements.uploadStatus.textContent = message;
    }

    function syncUploadButton() {
        if (!elements.uploadButton || !elements.uploadLabel) {
            return;
        }

        elements.uploadButton.disabled = state.uploading;
        elements.uploadLabel.textContent = state.uploading ? 'Đang tải ảnh...' : 'Tải ảnh lên thư viện';
    }

    function syncModalMode() {
        const pickerMode = Boolean(state.picker);

        if (elements.editorFields) {
            elements.editorFields.classList.toggle('hidden', pickerMode);
        }

        if (elements.insertText) {
            elements.insertText.textContent = pickerMode
                ? (state.picker?.buttonLabel || 'Chọn ảnh này')
                : 'Chèn ảnh vào editor';
        }
    }

    async function uploadImage() {
        if (state.uploading || !config.uploadUrl) {
            return;
        }

        const file = elements.uploadFile?.files?.[0];

        if (!file) {
            setUploadStatus('Chọn một file ảnh trước khi tải lên.', 'error');

            return;
        }

        state.uploading = true;
        syncUploadButton();
        setUploadStatus('Đang tải ảnh lên thư viện media...', 'info');

        const formData = new FormData();
        formData.append('image', file);

        if (elements.uploadName?.value.trim()) {
            formData.append('name', elements.uploadName.value.trim());
        }

        if (elements.uploadAlt?.value.trim()) {
            formData.append('alt', elements.uploadAlt.value.trim());
        }

        try {
            const response = await fetch(config.uploadUrl, {
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                method: 'POST',
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(firstErrorMessage(payload) || 'Không thể tải ảnh lên thư viện lúc này.');
            }

            state.query = '';
            state.page = 1;
            state.selected = payload.data || null;
            state.collection = payload.data?.collection_name || '';
            state.model = payload.data?.model_type || '';

            if (elements.search) {
                elements.search.value = '';
            }

            resetUploadForm();
            setUploadStatus(payload.message || 'Đã tải ảnh lên thư viện media.', 'success');
            await fetchImages();
            renderSelection();
        } catch (error) {
            setUploadStatus(error?.message || 'Không thể tải ảnh lên thư viện lúc này.', 'error');
        } finally {
            state.uploading = false;
            syncUploadButton();
        }
    }
}

function buildImageHtml({ alt, className, mediaId, src, style }) {
    const attributes = [
        `src="${escapeAttribute(src)}"`,
        `alt="${escapeAttribute(alt)}"`,
        `data-media-id="${escapeAttribute(String(mediaId))}"`,
        'loading="lazy"',
    ];

    if (className) {
        attributes.push(`class="${escapeAttribute(className)}"`);
    }

    if (style) {
        attributes.push(`style="${escapeAttribute(style)}"`);
    }

    return `<p><img ${attributes.join(' ')}></p><p><br></p>`;
}

function buildToolbar(toolbar, { allowImages, mode }) {
    if (mode !== 'rich') {
        toolbar.classList.add('hidden');
        toolbar.innerHTML = '';

        return false;
    }

    toolbar.classList.remove('hidden');
    toolbar.innerHTML = `
        <span class="ql-formats">
            <select class="ql-header">
                <option selected></option>
                <option value="2"></option>
                <option value="3"></option>
                <option value="4"></option>
            </select>
        </span>
        <span class="ql-formats">
            <button class="ql-bold" type="button"></button>
            <button class="ql-italic" type="button"></button>
            <button class="ql-underline" type="button"></button>
            <button class="ql-strike" type="button"></button>
        </span>
        <span class="ql-formats">
            <select class="ql-color"></select>
        </span>
        <span class="ql-formats">
            <button class="ql-blockquote" type="button"></button>
            <button class="ql-code-block" type="button"></button>
        </span>
        <span class="ql-formats">
            <button class="ql-list" value="ordered" type="button"></button>
            <button class="ql-list" value="bullet" type="button"></button>
            <button class="ql-link" type="button"></button>
        </span>
        <span class="ql-formats">
            <button class="ql-clean" type="button"></button>
            ${allowImages ? '<button class="ql-insertMedia" type="button" aria-label="Chèn ảnh"><span class="admin-quill-toolbar-label"><i class="fa-regular fa-image"></i><span>Chèn ảnh</span></span></button>' : ''}
        </span>
        <span class="ql-formats admin-quill-table-menu" data-quill-table-menu>
            <button class="admin-quill-table-menu__trigger" type="button" aria-expanded="false" aria-haspopup="true" aria-label="Mở công cụ bảng" title="Công cụ bảng" data-quill-table-menu-trigger>
                <span class="admin-quill-toolbar-label"><i class="fa-solid fa-table"></i><span>Bảng</span><i class="fa-solid fa-chevron-down admin-quill-table-menu__chevron"></i></span>
            </button>
            <span class="admin-quill-table-menu__panel" data-quill-table-menu-panel hidden>
                <button class="ql-insertTable admin-quill-table-command" type="button" aria-label="Chèn bảng" title="Chèn bảng"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-table"></i><span>Chèn bảng</span></span></button>
                <button class="ql-insertTableRow admin-quill-table-command" type="button" aria-label="Thêm dòng bảng" title="Thêm dòng bên dưới"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-plus"></i><span>Thêm dòng</span></span></button>
                <button class="ql-insertTableColumn admin-quill-table-command" type="button" aria-label="Thêm cột bảng" title="Thêm cột bên phải"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-plus"></i><span>Thêm cột</span></span></button>
                <button class="ql-deleteTableRow admin-quill-table-command" type="button" aria-label="Xóa dòng bảng" title="Xóa dòng hiện tại"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-minus"></i><span>Xóa dòng</span></span></button>
                <button class="ql-deleteTableColumn admin-quill-table-command" type="button" aria-label="Xóa cột bảng" title="Xóa cột hiện tại"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-minus"></i><span>Xóa cột</span></span></button>
                <button class="ql-setTableId admin-quill-table-command" type="button" aria-label="Gán ID bảng" title="Gán ID cho bảng"><span class="admin-quill-toolbar-label"><i class="fa-solid fa-hashtag"></i><span>Gán ID</span></span></button>
                <button class="ql-deleteTable admin-quill-table-command" type="button" aria-label="Xóa bảng" title="Xóa bảng hiện tại"><span class="admin-quill-toolbar-label"><i class="fa-regular fa-trash-can"></i><span>Xóa bảng</span></span></button>
            </span>
        </span>
    `;

    return toolbar;
}

function escapeAttribute(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

function escapeText(value) {
    return escapeAttribute(value);
}

function firstErrorMessage(payload) {
    const errors = payload?.errors;

    if (!errors || typeof errors !== 'object') {
        return '';
    }

    const firstGroup = Object.values(errors)[0];

    if (Array.isArray(firstGroup) && firstGroup[0]) {
        return String(firstGroup[0]);
    }

    return '';
}

function getEditorRoots(root) {
    if (!root) {
        return [];
    }

    const roots = [];

    if (root.matches?.(EDITOR_SELECTOR)) {
        roots.push(root);
    }

    roots.push(...root.querySelectorAll?.(EDITOR_SELECTOR) || []);

    return roots;
}

function getMetaContent(name) {
    return document.querySelector(`meta[name="${name}"]`)?.content || '';
}

function closeTableToolbarMenus(except = null) {
    document.querySelectorAll('[data-quill-table-menu].is-open').forEach((menu) => {
        if (menu === except) {
            return;
        }

        const trigger = menu.querySelector('[data-quill-table-menu-trigger]');
        const panel = menu.querySelector('[data-quill-table-menu-panel]');

        trigger?.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');

        if (panel) {
            panel.hidden = true;
        }
    });
}

function insertTableFromToolbar(quill, wrapper) {
    const table = quill.getModule('table');

    if (!table || typeof table.insertTable !== 'function') {
        window.alert?.('Editor chưa bật module bảng.');

        return;
    }

    const rows = promptTableDimension('Số dòng của bảng', DEFAULT_TABLE_ROWS, MAX_TABLE_ROWS);

    if (rows === null) {
        return;
    }

    const columns = promptTableDimension('Số cột của bảng', DEFAULT_TABLE_COLUMNS, MAX_TABLE_COLUMNS);

    if (columns === null) {
        return;
    }

    quill.focus();

    if (!quill.getSelection()) {
        quill.setSelection(Math.max(quill.getLength() - 1, 0), 0, 'silent');
    }

    table.insertTable(rows, columns);
    syncSourceValue(wrapper);
}

function getActiveTableContext(quill, actionLabel) {
    const table = quill.getModule('table');

    if (!table || typeof table.getTable !== 'function') {
        window.alert?.('Editor chưa bật module bảng.');

        return null;
    }

    const [tableBlot] = table.getTable(quill.getSelection(true));

    if (!tableBlot?.domNode) {
        window.alert?.(`Đặt con trỏ vào một ô bảng trước khi ${actionLabel}.`);

        return null;
    }

    return { table, tableNode: tableBlot.domNode };
}

function isSafeHtmlId(value) {
    return /^[A-Za-z][A-Za-z0-9_.:-]{0,63}$/.test(value);
}

function editActiveTableId(quill, wrapper) {
    const context = getActiveTableContext(quill, 'gán ID bảng');

    if (!context) {
        return;
    }

    const currentId = context.tableNode.getAttribute('id') || '';
    const value = window.prompt?.('ID bảng: chữ/số và các ký tự - _ . :, bắt đầu bằng chữ. Để trống để xóa ID.', currentId);

    if (value === null || typeof value === 'undefined') {
        return;
    }

    const nextId = value.trim();

    if (nextId === '') {
        context.tableNode.removeAttribute('id');
        syncSourceValue(wrapper);

        return;
    }

    if (!isSafeHtmlId(nextId)) {
        window.alert?.('ID bảng không hợp lệ. Ví dụ hợp lệ: tour-price-table hoặc lich_khoi_hanh_2026.');

        return;
    }

    const isDuplicate = Array.from(quill.root.querySelectorAll('table[id]'))
        .some((table) => table !== context.tableNode && table.getAttribute('id') === nextId);

    if (isDuplicate) {
        window.alert?.('ID này đã được dùng trong editor hiện tại.');

        return;
    }

    context.tableNode.setAttribute('id', nextId);
    syncSourceValue(wrapper);
}

function runTableOperation(quill, wrapper, method, actionLabel) {
    const context = getActiveTableContext(quill, actionLabel);

    if (!context || typeof context.table[method] !== 'function') {
        return;
    }

    context.table[method]();
    syncSourceValue(wrapper);
}

function setupTableToolbarMenu(toolbarContainer) {
    const menu = toolbarContainer.querySelector('[data-quill-table-menu]');
    const trigger = menu?.querySelector('[data-quill-table-menu-trigger]');
    const panel = menu?.querySelector('[data-quill-table-menu-panel]');

    if (!menu || !trigger || !panel) {
        return;
    }

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const nextOpen = !menu.classList.contains('is-open');

        closeTableToolbarMenus(nextOpen ? menu : null);
        menu.classList.toggle('is-open', nextOpen);
        trigger.setAttribute('aria-expanded', String(nextOpen));
        panel.hidden = !nextOpen;
    });

    panel.addEventListener('click', (event) => {
        if (event.target.closest?.('button')) {
            window.setTimeout(() => closeTableToolbarMenus(), 0);
        }
    });

    menu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        event.preventDefault();
        closeTableToolbarMenus();
        trigger.focus();
    });
}

function initializeEditor(wrapper) {
    if (editors.has(wrapper)) {
        return;
    }

    const source = wrapper.querySelector('[data-quill-source]');
    const toolbar = wrapper.querySelector('[data-quill-toolbar]');
    const surface = wrapper.querySelector('[data-quill-editor]');

    if (!source || !toolbar || !surface) {
        return;
    }

    const mode = wrapper.dataset.mode || 'plain';
    const allowImages = wrapper.dataset.allowImages === 'true';
    const toolbarContainer = buildToolbar(toolbar, { allowImages, mode });
    const modules = mode === 'rich' ? { table: true } : {};

    if (toolbarContainer) {
        const handlers = {
            insertTable() {
                insertTableFromToolbar(this.quill, wrapper);
            },
            insertTableRow() {
                runTableOperation(this.quill, wrapper, 'insertRowBelow', 'thêm dòng');
            },
            insertTableColumn() {
                runTableOperation(this.quill, wrapper, 'insertColumnRight', 'thêm cột');
            },
            deleteTableRow() {
                runTableOperation(this.quill, wrapper, 'deleteRow', 'xóa dòng');
            },
            deleteTableColumn() {
                runTableOperation(this.quill, wrapper, 'deleteColumn', 'xóa cột');
            },
            deleteTable() {
                runTableOperation(this.quill, wrapper, 'deleteTable', 'xóa bảng');
            },
            setTableId() {
                editActiveTableId(this.quill, wrapper);
            },
        };

        if (allowImages) {
            handlers.insertMedia = function insertMedia() {
                ensureMediaBrowser()?.open(wrapper);
            };
        }

        modules.toolbar = {
            container: toolbarContainer,
            handlers,
        };

        setupTableToolbarMenu(toolbarContainer);
    } else {
        modules.toolbar = false;
    }

    const quill = new Quill(surface, {
        modules,
        placeholder: wrapper.dataset.placeholder || '',
        theme: 'snow',
    });

    setEditorValue(quill, source.value, mode);
    editors.set(wrapper, { mode, quill, source });

    quill.on('text-change', (_delta, _oldDelta, sourceType) => {
        if (sourceType === 'silent') {
            return;
        }

        syncSourceValue(wrapper);
    });
}

function normalizePlainText(value) {
    return String(value)
        .replaceAll('\u00a0', ' ')
        .replace(/\r\n?/g, '\n')
        .replace(/\n$/, '')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function serializeEditor(instance) {
    if (instance.mode === 'plain') {
        return normalizePlainText(instance.quill.getText());
    }

    const hasVisibleText = instance.quill.getText().replaceAll('\u00a0', ' ').trim() !== '';
    const hasEmbed = Boolean(instance.quill.root.querySelector('img, iframe, table, video'));

    if (!hasVisibleText && !hasEmbed) {
        return '';
    }

    const html = instance.quill.root.innerHTML.trim();

    if (!html || html === EMPTY_RICH_VALUE) {
        return '';
    }

    return html;
}

function promptTableDimension(label, fallback, max) {
    const value = window.prompt?.(`${label} (1-${max})`, String(fallback));

    if (value === null || typeof value === 'undefined') {
        return null;
    }

    const parsed = Number.parseInt(value.trim(), 10);

    if (!Number.isInteger(parsed) || parsed < 1 || parsed > max) {
        window.alert?.(`Vui lòng nhập số từ 1 đến ${max}.`);

        return null;
    }

    return parsed;
}

function setEditorValue(quill, value, mode) {
    quill.setText('', 'silent');

    if (!value) {
        return;
    }

    if (mode === 'plain') {
        quill.setText(normalizePlainText(value), 'silent');

        return;
    }

    quill.clipboard.dangerouslyPasteHTML(value, 'silent');
}

function syncSelectOptions(select, options, currentValue, defaultLabel) {
    if (!select) {
        return;
    }

    select.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = defaultLabel;
    select.appendChild(defaultOption);

    options.forEach((option) => {
        const element = document.createElement('option');
        element.value = option.value;
        element.textContent = option.label;
        select.appendChild(element);
    });

    select.value = currentValue || '';
}

function syncSourceValue(wrapper) {
    const instance = editors.get(wrapper);

    if (!instance) {
        return;
    }

    const value = serializeEditor(instance);

    if (value === instance.source.value) {
        return;
    }

    instance.source.value = value;
    instance.source.dispatchEvent(new Event('input', { bubbles: true }));
    instance.source.dispatchEvent(new Event('change', { bubbles: true }));
}

document.addEventListener('click', (event) => {
    if (!event.target.closest?.('[data-quill-table-menu]')) {
        closeTableToolbarMenus();
    }

    const trigger = event.target.closest?.('[data-admin-media-picker-trigger]');

    if (!trigger) {
        return;
    }

    event.preventDefault();

    ensureMediaBrowser()?.openPicker({
        buttonLabel: trigger.dataset.buttonLabel || 'Chọn ảnh này',
        componentId: trigger.dataset.livewireId || trigger.closest('[wire\\:id]')?.getAttribute('wire:id') || '',
        context: trigger.dataset.pickContext || '',
        method: trigger.dataset.pickMethod || 'selectLibraryMedia',
        target: trigger.dataset.pickTarget || '',
        altTarget: trigger.dataset.pickAltTarget || '',
    });
});
