/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
let updaterVisible = false;

function hideUpdater() {
    const updater = document.getElementById('bfmw_updater_simple');
    if (updater) {
        updater.classList.add('hide');
        updater.setAttribute('aria-hidden', 'true');
    }
    updaterVisible = false;
}

function updateUpdaterPosition(anchorElement) {
    const updater = document.getElementById('bfmw_updater_simple');
    if (!updater || !anchorElement) {
        return;
    }

    const updaterRect = updater.getBoundingClientRect();
    const anchorRect = anchorElement.getBoundingClientRect();
    const scrollX = window.scrollX || window.pageXOffset;
    const scrollY = window.scrollY || window.pageYOffset;
    const viewportWidth = document.documentElement.clientWidth;

    let left = anchorRect.left + scrollX;
    const top = anchorRect.bottom + scrollY + 8;

    if (left + updaterRect.width > viewportWidth + scrollX - 16) {
        left = viewportWidth + scrollX - updaterRect.width - 16;
    }

    if (left < scrollX + 16) {
        left = scrollX + 16;
    }

    updater.style.top = `${top}px`;
    updater.style.left = `${left}px`;
}

function showUpdater(event, modifierId, encodedParameters, csrfToken, csrfId, area = false) {
    hideUpdater();

    const updater = document.getElementById('bfmw_updater_simple');
    const textInput = document.getElementById('bfmw_update_field_text');
    const textareaInput = document.getElementById('bfmw_update_field_textarea');
    const csrfTokenInput = document.getElementById('bfmw_update_field_csrf_token');
    const csrfIdInput = document.getElementById('bfmw_update_field_csrf_id');

    if (!updater || !textInput || !textareaInput || !csrfTokenInput || !csrfIdInput) {
        return;
    }

    const activeElement = area ? textareaInput : textInput;
    const inactiveElement = area ? textInput : textareaInput;

    activeElement.dataset.encodedParameters = encodedParameters;
    activeElement.dataset.modifierId = modifierId;
    activeElement.value = document.getElementById(modifierId)?.textContent || '';
    csrfTokenInput.value = csrfToken;
    csrfIdInput.value = csrfId;

    activeElement.classList.remove('hide');
    inactiveElement.classList.add('hide');

    updater.classList.remove('hide');
    updater.setAttribute('aria-hidden', 'false');

    const anchorElement = event?.currentTarget || event?.target || document.getElementById(modifierId);
    updateUpdaterPosition(anchorElement);

    activeElement.focus();
    if (!area) {
        activeElement.select();
    }

    updaterVisible = true;

    if (event?.stopPropagation) {
        event.stopPropagation();
    }
}

function sendUpdaterValue(element, csrfToken = null, csrfId = null) {
    if (!element || !element.dataset?.modifierId || !element.dataset?.encodedParameters) {
        return;
    }

    const modifier = document.getElementById(element.dataset.modifierId);
    const nextValue = element.value;

    const onSuccess = function () {
        if (modifier) {
            modifier.textContent = nextValue;
        }

        document
            .querySelectorAll(`[data-bfmw-modifier="${element.dataset.modifierId}"]`)
            .forEach((trigger) => {
                trigger.dataset.bfmwValue = nextValue;
            });

        hideUpdater();
    };

    bfmw_bind(
        element.dataset.encodedParameters,
        nextValue,
        element,
        csrfId,
        csrfToken,
        null,
        onSuccess
    );
}

function submitUpdater() {
    const textInput = document.getElementById('bfmw_update_field_text');
    const textareaInput = document.getElementById('bfmw_update_field_textarea');
    const activeElement = !textInput?.classList.contains('hide') ? textInput : textareaInput;
    const csrfIdInput = document.getElementById('bfmw_update_field_csrf_id');
    const csrfTokenInput = document.getElementById('bfmw_update_field_csrf_token');
    sendUpdaterValue(activeElement, csrfTokenInput.value ?? '', csrfIdInput.value ?? '');
}

function registerUpdaterTriggers() {
    const triggers = document.querySelectorAll('[data-bfmw-updater]');

    triggers.forEach((trigger) => {
        trigger.removeAttribute('data-bfmw-updater');
        trigger.addEventListener('click', function (event) {
            showUpdater(
                event,
                trigger.dataset.bfmwModifier,
                trigger.dataset.encodedParameters,
                trigger.dataset.csrfToken,
                trigger.dataset.csrfId,
                trigger.dataset.bfmwArea === '1'
            );
        });
    });
}

toDoOnLoad(function () {
    const updater = document.getElementById('bfmw_updater_simple');
    const submitButton = document.getElementById('bfmw_update_submit');
    const textInput = document.getElementById('bfmw_update_field_text');

    if (!updater) {
        return;
    }

    registerUpdaterTriggers();

    submitButton?.addEventListener('click', function (event) {
        event.preventDefault();
        submitUpdater();
    });

    textInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitUpdater();
        }
    });

    updater.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    window.addEventListener('resize', function () {
        if (updaterVisible) {
            const visibleInput = !textInput?.classList.contains('hide')
                ? textInput
                : document.getElementById('bfmw_update_field_textarea');
            const anchor = document.getElementById(visibleInput?.dataset?.bfmw2 || '');
            updateUpdaterPosition(anchor);
        }
    });

    window.addEventListener('click', function (event) {
        if (!updaterVisible) {
            return;
        }

        if (!updater.contains(event.target)) {
            hideUpdater();
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && updaterVisible) {
            hideUpdater();
        }
    });
});
