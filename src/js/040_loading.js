/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
toDoOnLoad(function() {
    const overlay = document.getElementById('bfmw-loading');
    if (!overlay) return;

    let active = false;
    let overlayTimeout = null;

    function showLoading() {
        overlayTimeout = setTimeout(()=> {
            if (active) return;
            active = true;
            overlay.classList.add('is-active');
            document.body.classList.add('bfmw-loading-lock');
        }, 250);
    }

    function hideLoading() {
        if (overlayTimeout) {
            clearTimeout( overlayTimeout);
        }
        active = false;
        overlay.classList.remove('is-active');
        document.body.classList.remove('bfmw-loading-lock');

    }

    document.addEventListener('submit',function (e) {
        if (e.target && e.target.dataset.noLoading === '1') return;
        showLoading();
    }, true);

    document.addEventListener('click', function (e) {
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;

        const href = a.getAttribute('href') || '';
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (a.target && a.target.toLowerCase() === '_blank') return;
        if (a.hasAttribute('download')) return;

        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        showLoading();
    }, true);

    window.bfmwLoading = { show: showLoading, hide: hideLoading };

    window.addEventListener('beforeunload', function () {
        showLoading();
    });

    window.addEventListener('pageshow', function () {
        hideLoading();
    });
});