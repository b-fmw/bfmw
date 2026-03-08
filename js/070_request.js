/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
toDoOnLoad(function () {
    document.querySelectorAll('a[data-bfmw-method][href]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            const link = event.target.closest && event.target.closest('a[data-bfmw-method][href]');
            if (!link) return;

            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            if (link.target && link.target.toLowerCase() === '_blank') return;
            if (link.hasAttribute('download')) return;

            const method = (link.dataset.bfmwMethod || '').toUpperCase();
            if (!['POST', 'PUT', 'DELETE'].includes(method)) return;

            event.preventDefault();

            const form = document.createElement('form');
            form.method = 'POST';
            const url = new URL(link.getAttribute('href') || window.location.href, window.location.href);
            const routingPage = url.searchParams.get('p');
            if (routingPage !== null) {
                form.action = (url.pathname || window.location.pathname) + '?p=' + encodeURIComponent(routingPage);
                url.searchParams.delete('p');
            } else {
                form.action = url.pathname || window.location.pathname;
            }
            form.style.display = 'none';

            url.searchParams.forEach(function (value, key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            });

            if (method !== 'POST') {
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = method;
                form.appendChild(methodInput);
            }

            const csrfToken = link.dataset.csrfToken || '';
            const csrfId = link.dataset.csrfId || '';
            if (csrfToken !== '' && csrfId !== '') {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'csrf_token';
                tokenInput.value = csrfToken;
                form.appendChild(tokenInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'csrf_id';
                idInput.value = csrfId;
                form.appendChild(idInput);
            }

            document.body.appendChild(form);
            form.submit();
        }, true);
    })
})

