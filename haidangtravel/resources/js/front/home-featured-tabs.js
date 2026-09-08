export function initTabbedTourLists() {
    const configs = [
        {
            buttonDataKey: 'homeFeaturedTab',
            summaryDescriptionSelector: '[data-home-featured-summary-description]',
            summaryLinkSelector: '[data-home-featured-summary-link]',
            summaryTitleSelector: '[data-home-featured-summary-title]',
            panelDataKey: 'homeFeaturedPanel',
            panelSelector: '[data-home-featured-panel]',
            readyKey: 'homeFeaturedTabsReady',
            rootSelector: '[data-home-featured-tabs]',
            tabSelector: '[data-home-featured-tab]',
            tabDescriptionDataKey: 'homeFeaturedTabDescription',
            tabTitleDataKey: 'homeFeaturedTabTitle',
            tabUrlDataKey: 'homeFeaturedTabUrl',
        },
        {
            buttonDataKey: 'tourListTab',
            summaryDescriptionSelector: '[data-tour-list-summary-description]',
            summaryLinkSelector: '[data-tour-list-summary-link]',
            summaryTitleSelector: '[data-tour-list-summary-title]',
            panelDataKey: 'tourListPanel',
            panelSelector: '[data-tour-list-panel]',
            readyKey: 'tourListTabsReady',
            rootSelector: '[data-tour-list-tabs]',
            tabSelector: '[data-tour-list-tab]',
            tabDescriptionDataKey: 'tourListTabDescription',
            tabTitleDataKey: 'tourListTabTitle',
            tabUrlDataKey: 'tourListTabUrl',
        },
        {
            buttonDataKey: 'tourDepartureTab',
            summaryDescriptionSelector: '[data-tour-departure-summary-description]',
            summaryLinkSelector: '[data-tour-departure-summary-link]',
            summaryTitleSelector: '[data-tour-departure-summary-title]',
            panelDataKey: 'tourDeparturePanel',
            panelSelector: '[data-tour-departure-panel]',
            readyKey: 'tourDepartureTabsReady',
            rootSelector: '[data-tour-departure-tabs]',
            tabSelector: '[data-tour-departure-tab]',
            tabDescriptionDataKey: 'tourDepartureTabDescription',
            tabTitleDataKey: 'tourDepartureTabTitle',
            tabUrlDataKey: 'tourDepartureTabUrl',
        },
    ];

    const activeClasses = [
        'border-transparent',
        'bg-[linear-gradient(135deg,#FF6A00,#FF8C00)]',
        'text-white',
        'shadow-[0_20px_45px_-24px_rgba(255,106,0,0.58)]',
    ];
    const inactiveClasses = [
        'border-slate-200',
        'bg-white',
        'text-slate-600',
        'hover:border-orange-200',
        'hover:text-primary',
    ];
    const counterActiveClasses = ['text-white/90'];
    const counterInactiveClasses = ['text-slate-500'];

    const applyDescriptionState = (element, value) => {
        if (!element) {
            return;
        }

        element.textContent = value ?? '';
        element.classList.toggle('hidden', !(value ?? '').trim());
    };

    configs.forEach((config) => {
        document.querySelectorAll(config.rootSelector).forEach((root) => {
            if (root.dataset[config.readyKey] === '1') {
                return;
            }

            root.dataset[config.readyKey] = '1';

            const buttons = Array.from(root.querySelectorAll(config.tabSelector));
            const panels = Array.from(root.querySelectorAll(config.panelSelector));
            const summaryTitle = root.querySelector(config.summaryTitleSelector);
            const summaryDescription = root.querySelector(config.summaryDescriptionSelector);
            const summaryLink = root.querySelector(config.summaryLinkSelector);

            if (!buttons.length || !panels.length) {
                return;
            }

            const activateTab = (targetTab) => {
                const activeButton = buttons.find((button) => button.dataset[config.buttonDataKey] === targetTab);

                buttons.forEach((button) => {
                    const isActive = button.dataset[config.buttonDataKey] === targetTab;
                    const counter = button.querySelector('span:last-child');

                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    button.tabIndex = isActive ? 0 : -1;

                    activeClasses.forEach((className) => button.classList.toggle(className, isActive));
                    inactiveClasses.forEach((className) => button.classList.toggle(className, !isActive));

                    if (counter) {
                        counterActiveClasses.forEach((className) => counter.classList.toggle(className, isActive));
                        counterInactiveClasses.forEach((className) => counter.classList.toggle(className, !isActive));
                    }
                });

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset[config.panelDataKey] !== targetTab;
                });

                if (!activeButton) {
                    return;
                }

                if (summaryTitle) {
                    summaryTitle.textContent = activeButton.dataset[config.tabTitleDataKey] ?? '';
                }

                applyDescriptionState(summaryDescription, activeButton.dataset[config.tabDescriptionDataKey] ?? '');

                if (summaryLink && activeButton.dataset[config.tabUrlDataKey]) {
                    summaryLink.href = activeButton.dataset[config.tabUrlDataKey];
                }

                window.requestAnimationFrame(() => {
                    document.dispatchEvent(new CustomEvent('frontsite:refresh-card-carousels'));
                });
            };

            root.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                const button = event.target.closest(config.tabSelector);

                if (!(button instanceof HTMLElement) || !root.contains(button)) {
                    return;
                }

                event.preventDefault();
                activateTab(button.dataset[config.buttonDataKey]);
            });

            buttons.forEach((button, index) => {
                button.addEventListener('keydown', (event) => {
                    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) {
                        return;
                    }

                    event.preventDefault();

                    let nextIndex = index;

                    if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                        nextIndex = (index + 1) % buttons.length;
                    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                        nextIndex = (index - 1 + buttons.length) % buttons.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = buttons.length - 1;
                    }

                    const nextButton = buttons[nextIndex];

                    nextButton.focus();
                    activateTab(nextButton.dataset[config.buttonDataKey]);
                });
            });

            const initiallySelected = buttons.find((button) => button.getAttribute('aria-selected') === 'true')?.dataset[config.buttonDataKey]
                ?? buttons[0]?.dataset[config.buttonDataKey];

            if (initiallySelected) {
                activateTab(initiallySelected);
            }
        });
    });
}
