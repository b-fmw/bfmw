/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
function normalizeBindingValue(element, value) {
    if (element?.type === 'checkbox') {
        return element.checked ? value : '0';
    }

    if (typeof value === 'string') {
        return value.trim();
    }

    return value ?? '';
}

function resolveColorTarget(objColor) {
    if (typeof objColor === 'string') {
        return document.getElementById(objColor);
    }

    return objColor ?? null;
}

function applyBindingColor(target, success) {
    if (!target) {
        return;
    }

    target.style.backgroundColor = success ? 'var(--background-success)' : 'var(--background-alert)';
}

function bfmw_bind(encodedParameters, bindingValue, bindingElement, csrfId, csrfToken, objectToColorise, toDoAfterSuccess, toDoInCaseOfError = null) {
    const target = resolveColorTarget(objectToColorise);
    const normalizedValue = normalizeBindingValue(bindingElement, bindingValue);

    const params = new URLSearchParams({
        bind: 'doing',
        encoded_parameters: encodedParameters ?? '',
        binding_value: String(normalizedValue),
        csrf_id: csrfId ?? '',
        csrf_token: csrfToken ?? ''
    });

    window.bfmwLoading?.show?.();

    return fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    }).then(async (response) => {
        if (!response.ok) {
            throw new Error(`Bind request failed with status ${response.status}`);
        }

        const responseText = await response.text();
        let jsonPayload = null;

        try {
            jsonPayload = JSON.parse(responseText);
        } catch {
            jsonPayload = null;
        }

        return normalizeBindingResponse(jsonPayload, responseText);
    }).then((bindingResponse) => {
        applyBindingColor(target, bindingResponse.success);

        if (bindingResponse.success) {
            if (typeof toDoAfterSuccess === 'function') {
                toDoAfterSuccess(bindingResponse);
            }
        } else if (typeof toDoInCaseOfError === 'function') {
            toDoInCaseOfError(bindingResponse);
        }

        showBfmwMessage(bindingResponse.type, bindingResponse.title, bindingResponse.message);
        return bindingResponse;
    }).catch((error) => {
        applyBindingColor(target, false);

        const networkResponse = {
            success: false,
            inProgress: false,
            type: 'error',
            title: 'Erreur grave de traitement',
            message: error.message
        };

        if (typeof toDoInCaseOfError === 'function') {
            toDoInCaseOfError(networkResponse);
        }

        showBfmwMessage(networkResponse.type, networkResponse.title, networkResponse.message);
        return networkResponse;
    }).finally(() => {
        window.bfmwLoading?.hide?.();
    });
}

function normalizeBindingResponse(payload, fallbackText = '') {
    if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'success')) {
        return {
            success: payload.success === true,
            type: payload.type ?? '',
            title: payload.title ?? '',
            message: payload.message ?? '',
        };
    }

    return {
        success: false,
        type: '',
        title: '',
        message: fallbackText
    };
}

toDoOnLoad(() => {
    const triggers = document.querySelectorAll('[data-bfmw-binding]');
    triggers.forEach((trigger) => {
        trigger.removeAttribute('data-bfmw-binding');
        trigger.addEventListener(((trigger instanceof HTMLInputElement && trigger.type === 'text') ? 'blur' : 'change'), () => {
            bfmw_bind(
                trigger.dataset.encodedParameters,
                trigger.value,
                trigger,
                trigger.dataset.csrfId,
                trigger.dataset.csrfToken,
                trigger
            );
        });
    });
});
