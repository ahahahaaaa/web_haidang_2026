const ROOT_SELECTOR = '[data-voucher-campaign]';
const VIEW_CODE_SELECTOR = '[data-voucher-view-code]';
const MODAL_SELECTOR = '[data-voucher-code-modal]';

const campaignVouchers = new Map();

let modal = null;
let activeVoucher = null;

export function initVoucherRedemptions() {
    const roots = Array.from(document.querySelectorAll(ROOT_SELECTOR));

    if (roots.length === 0) {
        return;
    }

    roots.forEach((root) => {
        if (! (root instanceof HTMLElement) || root.dataset.voucherInitialized === 'true') {
            return;
        }

        root.dataset.voucherInitialized = 'true';
        bindRoot(root);
        void fetchRememberedVoucher(root);
    });

    document.addEventListener('frontsite:ajax-form-success', (event) => {
        const voucher = event.detail?.payload?.voucher;

        if (! voucher?.code || ! voucher?.campaign_slug) {
            return;
        }

        campaignVouchers.set(voucher.campaign_slug, voucher);
        revealCampaignRoot(voucher.campaign_slug);
        showVoucherModal(voucher);
    });
}

function bindRoot(root) {
    const button = root.querySelector(VIEW_CODE_SELECTOR);

    if (! (button instanceof HTMLButtonElement)) {
        return;
    }

    button.addEventListener('click', () => {
        const slug = root.dataset.voucherCampaignSlug || '';
        const voucher = campaignVouchers.get(slug);

        if (voucher) {
            showVoucherModal(voucher);
        }
    });
}

async function fetchRememberedVoucher(root) {
    const url = root.dataset.voucherRememberedUrl;

    if (! url) {
        return;
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json().catch(() => ({}));
        const voucher = payload?.voucher;

        if (! response.ok || ! voucher?.code || ! voucher?.campaign_slug) {
            return;
        }

        campaignVouchers.set(voucher.campaign_slug, voucher);
        root.classList.remove('hidden');
    } catch (error) {
        // Remembered voucher lookup is a convenience layer; the form itself remains usable.
    }
}

function revealCampaignRoot(slug) {
    document.querySelectorAll(ROOT_SELECTOR).forEach((root) => {
        if (! (root instanceof HTMLElement) || root.dataset.voucherCampaignSlug !== slug) {
            return;
        }

        root.classList.remove('hidden');
    });
}

function ensureModal() {
    const existing = document.querySelector(MODAL_SELECTOR);

    if (existing instanceof HTMLElement) {
        modal = existing;

        return modal;
    }

    modal = document.createElement('div');
    modal.className = 'voucher-code-modal hidden';
    modal.dataset.voucherCodeModal = 'true';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <button type="button" class="voucher-code-modal__backdrop" data-voucher-close aria-label="Đóng mã voucher"></button>
        <div class="voucher-code-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="voucher-code-modal-title" tabindex="-1">
            <button type="button" class="voucher-code-modal__close" data-voucher-close aria-label="Đóng mã voucher">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <div class="voucher-code-modal__media" data-voucher-frame-wrap hidden>
                <img src="" alt="" data-voucher-frame>
            </div>
            <p class="voucher-code-modal__kicker">Nhận voucher thành công</p>
            <h2 id="voucher-code-modal-title" class="voucher-code-modal__title" data-voucher-title></h2>
            <p class="voucher-code-modal__description" data-voucher-description></p>
            <div class="voucher-code-modal__code" data-voucher-code></div>
            <p class="voucher-code-modal__expiry" data-voucher-expiry></p>
            <button type="button" class="voucher-code-modal__download" data-voucher-download>
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                Tải ảnh voucher
            </button>
        </div>
    `;

    modal.querySelectorAll('[data-voucher-close]').forEach((trigger) => {
        trigger.addEventListener('click', closeVoucherModal);
    });

    const downloadButton = modal.querySelector('[data-voucher-download]');

    if (downloadButton instanceof HTMLButtonElement) {
        downloadButton.addEventListener('click', () => {
            if (activeVoucher?.code) {
                void downloadVoucherImage(activeVoucher);
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal?.getAttribute('aria-hidden') === 'false') {
            closeVoucherModal();
        }
    });

    document.body.appendChild(modal);

    return modal;
}

function showVoucherModal(voucher) {
    activeVoucher = voucher;
    const dialogRoot = ensureModal();
    const frameWrap = dialogRoot.querySelector('[data-voucher-frame-wrap]');
    const frame = dialogRoot.querySelector('[data-voucher-frame]');
    const title = dialogRoot.querySelector('[data-voucher-title]');
    const description = dialogRoot.querySelector('[data-voucher-description]');
    const code = dialogRoot.querySelector('[data-voucher-code]');
    const expiry = dialogRoot.querySelector('[data-voucher-expiry]');

    if (title) {
        title.textContent = voucher.campaign_title || 'Voucher Hải Đăng Travel';
    }

    if (description) {
        description.textContent = voucher.description || 'Lưu lại mã này để đội ngũ tư vấn áp dụng ưu đãi khi liên hệ với bạn.';
    }

    if (code) {
        code.textContent = voucher.code;
    }

    if (expiry) {
        expiry.textContent = voucher.valid_until_label
            ? (voucher.valid_until_note || `Áp dụng đến hết ngày ${voucher.valid_until_label}`)
            : 'Áp dụng theo điều kiện chiến dịch hiện tại.';
    }

    if (frame instanceof HTMLImageElement && frameWrap instanceof HTMLElement && voucher.frame_image_url) {
        frame.src = voucher.frame_image_url;
        frame.alt = voucher.campaign_title || 'Khung voucher Hải Đăng Travel';
        frameWrap.hidden = false;
    } else if (frameWrap instanceof HTMLElement) {
        frameWrap.hidden = true;
    }

    dialogRoot.classList.remove('hidden');
    dialogRoot.setAttribute('aria-hidden', 'false');
    document.body.classList.add('service-modal-open');
    dialogRoot.querySelector('[role="dialog"]')?.focus();
}

function closeVoucherModal() {
    if (! modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('service-modal-open');
}

async function downloadVoucherImage(voucher) {
    const canvas = document.createElement('canvas');
    canvas.width = 1200;
    canvas.height = 675;
    const context = canvas.getContext('2d');

    if (! context) {
        return;
    }

    await drawVoucherImage(context, canvas, voucher);

    try {
        const link = document.createElement('a');
        link.download = voucher.download_filename || `${slugify(voucher.code || 'voucher-hai-dang-travel')}.png`;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        const fallbackCanvas = document.createElement('canvas');
        fallbackCanvas.width = canvas.width;
        fallbackCanvas.height = canvas.height;
        const fallbackContext = fallbackCanvas.getContext('2d');

        if (! fallbackContext) {
            return;
        }

        drawVoucherFallback(fallbackContext, fallbackCanvas, voucher);
        const link = document.createElement('a');
        link.download = voucher.download_filename || `${slugify(voucher.code || 'voucher-hai-dang-travel')}.png`;
        link.href = fallbackCanvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        link.remove();
    }
}

async function drawVoucherImage(context, canvas, voucher) {
    drawVoucherFallback(context, canvas, voucher);

    if (! voucher.frame_image_url) {
        return;
    }

    try {
        const image = await loadImage(voucher.frame_image_url);
        context.save();
        drawCoverImage(context, image, 0, 0, canvas.width, canvas.height);
        context.restore();
        drawVoucherOverlay(context, canvas, voucher);
    } catch (error) {
        drawVoucherFallback(context, canvas, voucher);
    }
}

function drawVoucherFallback(context, canvas, voucher) {
    const gradient = context.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#fff8e6');
    gradient.addColorStop(0.58, '#fff2d2');
    gradient.addColorStop(1, '#ffe0a3');
    context.fillStyle = gradient;
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.fillStyle = 'rgba(255, 106, 0, 0.08)';
    context.beginPath();
    context.arc(1030, 80, 260, 0, Math.PI * 2);
    context.fill();

    context.strokeStyle = 'rgba(242, 138, 16, 0.45)';
    context.lineWidth = 8;
    roundedRect(context, 48, 48, canvas.width - 96, canvas.height - 96, 42);
    context.stroke();

    drawVoucherOverlay(context, canvas, voucher);
}

function drawVoucherOverlay(context, canvas, voucher) {
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillStyle = '#b85f13';
    context.font = '700 30px Arial, sans-serif';
    context.fillText('HẢI ĐĂNG TRAVEL', canvas.width / 2, 112);

    context.fillStyle = '#f28a10';
    context.font = '900 82px Arial, sans-serif';
    context.fillText(voucher.campaign_title || 'Voucher du lịch', canvas.width / 2, 230);

    context.fillStyle = '#1f3348';
    context.font = '600 30px Arial, sans-serif';
    wrapCanvasText(context, voucher.description || 'Lưu lại mã này để đội ngũ tư vấn áp dụng ưu đãi khi liên hệ với bạn.', canvas.width / 2, 315, 900, 40);

    context.fillStyle = '#fffaf0';
    roundedRect(context, 250, 410, 700, 110, 28);
    context.fill();
    context.strokeStyle = 'rgba(242, 138, 16, 0.62)';
    context.lineWidth = 4;
    context.stroke();

    context.fillStyle = '#f27600';
    context.font = '900 54px Arial, sans-serif';
    context.fillText(voucher.code || '', canvas.width / 2, 466);

    context.fillStyle = '#475569';
    context.font = '600 28px Arial, sans-serif';
    context.fillText(voucher.valid_until_note || (voucher.valid_until_label ? `Áp dụng đến hết ngày ${voucher.valid_until_label}` : 'Áp dụng theo điều kiện chiến dịch hiện tại.'), canvas.width / 2, 575);
}

function drawCoverImage(context, image, x, y, width, height) {
    const ratio = Math.max(width / image.naturalWidth, height / image.naturalHeight);
    const targetWidth = image.naturalWidth * ratio;
    const targetHeight = image.naturalHeight * ratio;
    const targetX = x + (width - targetWidth) / 2;
    const targetY = y + (height - targetHeight) / 2;

    context.drawImage(image, targetX, targetY, targetWidth, targetHeight);
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

function roundedRect(context, x, y, width, height, radius) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.arcTo(x + width, y, x + width, y + height, radius);
    context.arcTo(x + width, y + height, x, y + height, radius);
    context.arcTo(x, y + height, x, y, radius);
    context.arcTo(x, y, x + width, y, radius);
    context.closePath();
}

function wrapCanvasText(context, text, x, y, maxWidth, lineHeight) {
    const words = `${text}`.split(/\s+/);
    let line = '';
    let offsetY = 0;

    words.forEach((word, index) => {
        const testLine = line ? `${line} ${word}` : word;

        if (context.measureText(testLine).width > maxWidth && line) {
            context.fillText(line, x, y + offsetY);
            line = word;
            offsetY += lineHeight;
            return;
        }

        line = testLine;

        if (index === words.length - 1) {
            context.fillText(line, x, y + offsetY);
        }
    });
}

function slugify(value) {
    return `${value}`.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'voucher';
}
