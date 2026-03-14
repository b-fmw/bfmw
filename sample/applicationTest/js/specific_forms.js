/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

toDoOnLoad(() => {
    const triggers = document.querySelectorAll('[data-bfmw-manual-binding]');
    triggers.forEach((trigger) => {
        trigger.removeAttribute('data-bfmw-manual-binding');
        trigger.addEventListener(((trigger instanceof HTMLInputElement && trigger.type === 'text') ? 'blur' : 'change'), () => {
            bfmw_bind(
                trigger.dataset.encodedParameters,
                trigger.value,
                trigger,
                trigger.dataset.csrfId,
                trigger.dataset.csrfToken,
                trigger,
                () => { document.getElementById('id_binding_validator_done')?.classList.remove('hide'); },
                () => { document.getElementById('id_binding_validator_fail')?.classList.remove('hide'); },
            );
        });
    });
})
