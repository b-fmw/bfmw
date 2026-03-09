/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
let bfmwActiveModalCloseHandler = null;
let bfmwMessageQueue = [];

function sanitizeBfmwModalText(value) {
    return (value ?? '').toString().replace(/\\/g, '');
}

function resetBfmwModalHeaderClass(header) {
    header.classList.remove('is-info', 'is-warning', 'is-error', 'is-success');
}

function getBfmwModalElements() {
    return {
        modal: document.getElementById('myBfmwModal'),
        header: document.getElementById('Bfmw_modal_header'),
        title: document.getElementById('Bfmw_modal_title'),
        content: document.getElementById('Bfmw_modal_content'),
        closeButton: document.getElementById('close-bfmw-modal-qlkqsdf')
    };
}

function closeBfmwModal() {
    const { modal, content } = getBfmwModalElements();
    if (!modal) return;

    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('bfmw-modal-open');

    if (content) {
        content.innerHTML = '';
    }

    bfmwActiveModalCloseHandler = null;
}

function openBfmwModal() {
    const { modal } = getBfmwModalElements();
    if (!modal) return;

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('bfmw-modal-open');
}

function showBfmwMessage(messageType, messageTitle, message) {
    const { header, title, content } = getBfmwModalElements();
    if (!header || !title || !content) return;

    resetBfmwModalHeaderClass(header);

    switch ((messageType ?? '').toLowerCase()) {
        case 'info':
            header.classList.add('is-info');
            break;
        case 'warning':
            header.classList.add('is-warning');
            break;
        case 'success':
            header.classList.add('is-success');
            break;
        case 'error':
            header.classList.add('is-error');
            break;
        default : return;
    }

    title.textContent = sanitizeBfmwModalText(messageTitle);
    content.innerHTML = '<p>' + sanitizeBfmwModalText(message) + '</p>';

    openBfmwModal();
}

function showNextBfmwQueuedMessage() {
    const nextMessage = bfmwMessageQueue.shift();
    if (!nextMessage) {
        bfmwActiveModalCloseHandler = null;
        return;
    }

    showBfmwMessage(nextMessage.type, nextMessage.title, nextMessage.message);

    bfmwActiveModalCloseHandler = function () {
        closeBfmwModal();
        showNextBfmwQueuedMessage();
    };
}

toDoOnLoad(function () {
    bfmwMessageQueue = Array.from(document.querySelectorAll('[data-bfmw-show-message]')).map(function (element) {
        return {
            type: element.dataset.bfmwType,
            title: element.dataset.bfmwTitle,
            message: element.value
        };
    });

    showNextBfmwQueuedMessage();
});

toDoOnLoad(function () {
    const { modal, closeButton } = getBfmwModalElements();

    closeButton?.addEventListener('click', function () {
        if (typeof bfmwActiveModalCloseHandler === 'function') {
            bfmwActiveModalCloseHandler();
            return;
        }
        closeBfmwModal();
    });

    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            if (typeof bfmwActiveModalCloseHandler === 'function') {
                bfmwActiveModalCloseHandler();
                return;
            }
            closeBfmwModal();
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal?.style.display === 'block') {
            if (typeof bfmwActiveModalCloseHandler === 'function') {
                bfmwActiveModalCloseHandler();
                return;
            }
            closeBfmwModal();
        }
    });
});
