/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
function showResponsiveMenu() {
    document.querySelector("nav.menu")?.classList.toggle("responsive");
}
function showResponsiveMaster() {
    Object.assign(document.querySelector(".main .master")?.style ?? {}, { display: "flex", marginRight: "auto" });
    document.querySelector(".main .detail")?.style.setProperty("display","none");
}