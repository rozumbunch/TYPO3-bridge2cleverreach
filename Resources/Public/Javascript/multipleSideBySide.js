import Modal, { Types, Sizes } from '@typo3/backend/modal.js';
import Notification from '@typo3/backend/notification.js';

document.querySelectorAll('.js-multiselect-widget').forEach(widget => {
    const btnAdd = widget.querySelector('.js-add');
    const btnRemove = widget.querySelector('.js-remove');
    const searchInput = widget.querySelector('.js-available-search');
    const available = widget.querySelector('.js-available');
    const selected = widget.querySelector('.js-selected');
    const hidden = widget.querySelector('.js-hidden');

    if (!available || !selected || !hidden) {
        return;
    }

    const availablePool = Array.from(available.options);

    function syncHiddenField() {
        const values = Array.from(selected.options).map(opt => opt.value);
        hidden.value = values.join(',');
    }

    function matchesSearch(option) {
        if (!searchInput) {
            return true;
        }
        const term = searchInput.value.trim().toLowerCase();
        if (term === '') {
            return true;
        }
        return (option.textContent || '').toLowerCase().includes(term);
    }

    function filterAvailableOptions() {
        const selectedValues = new Set(
            Array.from(available.selectedOptions).map(option => option.value)
        );
        available.replaceChildren();
        availablePool.forEach(option => {
            if (!matchesSearch(option)) {
                return;
            }
            available.appendChild(option);
            option.selected = selectedValues.has(option.value);
        });
    }

    function moveSelected(from, to) {
        Array.from(from.selectedOptions).forEach(option => {
            const poolIndex = availablePool.indexOf(option);
            if (from === available && poolIndex !== -1) {
                availablePool.splice(poolIndex, 1);
            }
            to.appendChild(option);
            if (to === available && !availablePool.includes(option)) {
                availablePool.push(option);
            }
        });
        filterAvailableOptions();
        syncHiddenField();
        alignSelectedHeight();
    }

    function moveOnDoubleClick(from, to) {
        from.addEventListener('dblclick', event => {
            const option = event.target;
            if (option.tagName !== 'OPTION') {
                return;
            }
            Array.from(from.options).forEach(item => {
                item.selected = item === option;
            });
            moveSelected(from, to);
        });
    }

    function alignSelectedHeight() {
        const top = selected.getBoundingClientRect().top;
        const bottom = available.getBoundingClientRect().bottom;
        const height = bottom - top;
        if (height > 0) {
            selected.style.height = `${Math.round(height)}px`;
        }
    }

    if (btnAdd && btnRemove) {
        btnAdd.addEventListener('click', () => moveSelected(available, selected));
        btnRemove.addEventListener('click', () => moveSelected(selected, available));
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterAvailableOptions);
        searchInput.addEventListener('search', filterAvailableOptions);
        searchInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    }

    moveOnDoubleClick(available, selected);
    moveOnDoubleClick(selected, available);

    syncHiddenField();
    requestAnimationFrame(alignSelectedHeight);
    window.addEventListener('resize', alignSelectedHeight);
});

// Multi-page picker ("Pages" field), styled after pages.content_from_pid:
// opens TYPO3's own record browser (element browser) restricted to pages,
// and appends whatever gets picked there as a new option in the list.
document.querySelectorAll('.js-page-picker-browse').forEach(button => {
    const container = button.closest('.formengine-field-item');
    const list = container.querySelector('.js-page-picker-list');
    const value = container.querySelector('.js-page-picker-value');

    function syncPagesHiddenField() {
        value.value = Array.from(list.options).map(opt => opt.value).join(',');
    }

    button.addEventListener('click', () => {
        const params = new URLSearchParams({
            mode: 'db',
            allowedTypes: 'pages',
            useEvents: '1',
            // element-browser.js only calls addElement()/dispatches the
            // result when fieldReference is non-empty - the "data[table][uid][field]"
            // shape is what TYPO3 expects, but only the table/field parts are
            // actually read back out of it, so the uid segment is a dummy.
            fieldReference: 'data[pages][0][content_from_pid]',
        });
        const rootPageId = button.dataset.rootPageId || button.dataset.entryPoint || '';
        if (rootPageId) {
            params.set('expandPage', rootPageId);
        }
        const allowedPageIds = new Set(
            (button.dataset.sitePageIds || '').split(',').map(id => id.trim()).filter(id => id !== '')
        );

        const modal = Modal.advanced({
            type: Types.iframe,
            content: button.dataset.browserUrl + '&' + params.toString(),
            size: Sizes.large,
        });

        const restrictBrowserToSite = () => {
            const iframe = modal.querySelector('iframe');
            const browser = iframe?.contentDocument?.querySelector('typo3-backend-component-page-browser');
            if (!browser || !rootPageId) {
                return;
            }
            browser.setAttribute('alternative-entry-points', JSON.stringify([Number(rootPageId)]));
            browser.configuration = null;
            if (typeof browser.requestUpdate === 'function') {
                browser.requestUpdate();
            }
        };
        const watchBrowserFrame = () => {
            const iframe = modal.querySelector('iframe');
            if (!iframe) {
                return;
            }
            iframe.addEventListener('load', restrictBrowserToSite);
            restrictBrowserToSite();
        };
        modal.addEventListener('shown.bs.modal', watchBrowserFrame);
        requestAnimationFrame(watchBrowserFrame);

        // Modal is reused from the parent (top) frame's TYPO3.Modal singleton,
        // so the "typo3:element-browser:message" event - dispatched on the
        // record browser's <iframe> element, which lives in that same parent
        // document - never reaches this content-iframe's own `document`.
        // Listening on the modal element itself sidesteps that, since it's
        // the actual DOM node the event bubbles through either way.
        modal.addEventListener('typo3:element-browser:message', event => {
            const detail = event.detail || {};
            if (detail.actionName !== 'typo3:elementBrowser:elementAdded') {
                return;
            }

            const uid = String(detail.value).split('_').pop();
            if (allowedPageIds.size > 0 && !allowedPageIds.has(uid)) {
                return;
            }
            if (!Array.from(list.options).some(opt => opt.value === uid)) {
                const option = document.createElement('option');
                option.value = uid;
                const pageTitle = detail.label || uid;
                option.textContent = pageTitle + ' [' + uid + ']';
                list.appendChild(option);
                syncPagesHiddenField();
            }

            // The "+" icon in the record browser picks without closing, so
            // several pages can be added in one browsing session; clicking
            // the record title picks-and-closes.
            if (detail.close) {
                modal.hideModal();
            }
        });
    });
});

document.querySelectorAll('.js-content-picker-browse').forEach(button => {
    const container = button.closest('.formengine-field-item');
    const list = container.querySelector('.js-content-picker-list');
    const value = container.querySelector('.js-content-picker-value');
    const allowedElementIds = new Set(
        (button.dataset.allowedElementIds || '').split(',').map(id => id.trim()).filter(id => id !== '')
    );
    let elementLabels = {};
    try {
        elementLabels = JSON.parse(button.dataset.elementLabels || '{}');
    } catch {
        elementLabels = {};
    }

    function syncContentHiddenField() {
        value.value = Array.from(list.options).map(opt => opt.value).join(',');
    }

    button.addEventListener('click', () => {
        const params = new URLSearchParams({
            mode: 'db',
            allowedTypes: 'tt_content',
            useEvents: '1',
            fieldReference: 'data[tt_content][0][uid]',
        });
        const rootPageId = button.dataset.rootPageId || '';
        if (rootPageId) {
            params.set('expandPage', rootPageId);
        }

        const modal = Modal.advanced({
            type: Types.iframe,
            content: button.dataset.browserUrl + '&' + params.toString(),
            size: Sizes.large,
        });

        const restrictBrowserToSite = () => {
            const iframe = modal.querySelector('iframe');
            const browser = iframe?.contentDocument?.querySelector('typo3-backend-component-page-browser');
            if (!browser || !rootPageId) {
                return;
            }
            browser.setAttribute('alternative-entry-points', JSON.stringify([Number(rootPageId)]));
            browser.configuration = null;
            if (typeof browser.requestUpdate === 'function') {
                browser.requestUpdate();
            }
        };
        const watchBrowserFrame = () => {
            const iframe = modal.querySelector('iframe');
            if (!iframe) {
                return;
            }
            iframe.addEventListener('load', restrictBrowserToSite);
            restrictBrowserToSite();
        };
        modal.addEventListener('shown.bs.modal', watchBrowserFrame);
        requestAnimationFrame(watchBrowserFrame);

        modal.addEventListener('typo3:element-browser:message', event => {
            const detail = event.detail || {};
            if (detail.actionName !== 'typo3:elementBrowser:elementAdded') {
                return;
            }

            const uid = String(detail.value).split('_').pop();
            if (!allowedElementIds.has(uid)) {
                Notification.warning(
                    button.dataset.notAllowedTitle || 'Not allowed',
                    button.dataset.notAllowedMessage || 'This element is not allowed.'
                );
                if (detail.close) {
                    modal.hideModal();
                }
                return;
            }
            if (Array.from(list.options).some(opt => opt.value === uid)) {
                if (detail.close) {
                    modal.hideModal();
                }
                return;
            }

            const option = document.createElement('option');
            option.value = uid;
            option.textContent = elementLabels[uid] || detail.label || uid;
            list.appendChild(option);
            syncContentHiddenField();

            if (detail.close) {
                modal.hideModal();
            }
        });
    });
});

document.querySelectorAll('.js-content-picker-remove').forEach(button => {
    button.addEventListener('click', () => {
        const container = button.closest('.formengine-field-item');
        const list = container.querySelector('.js-content-picker-list');
        const value = container.querySelector('.js-content-picker-value');

        Array.from(list.selectedOptions).forEach(option => option.remove());
        value.value = Array.from(list.options).map(opt => opt.value).join(',');
    });
});

document.querySelectorAll('.js-page-picker-remove').forEach(button => {
    button.addEventListener('click', () => {
        const container = button.closest('.formengine-field-item');
        const list = container.querySelector('.js-page-picker-list');
        const value = container.querySelector('.js-page-picker-value');

        Array.from(list.selectedOptions).forEach(option => option.remove());
        value.value = Array.from(list.options).map(opt => opt.value).join(',');
    });
});
