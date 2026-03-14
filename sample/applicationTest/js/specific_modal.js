/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
toDoOnLoad(function(){
    document.getElementById("show_error")?.addEventListener("click", () => showBfmwMessage("error","Voici une erreur","Et voici la description de l'erreur"));
    document.getElementById("show_warning")?.addEventListener("click", () => showBfmwMessage("warning","Voici un warning","Et voici la description du warning"));
    document.getElementById("show_success")?.addEventListener("click", () => showBfmwMessage("success","Voici un succès","Et voici la description du succès"));
    document.getElementById("show_info")?.addEventListener("click", () => showBfmwMessage("info","Voici une info","Et voici la description de l'information, longue... Et voici la description de l'information, longue... Et voici la description de l'information, longue... Et voici la description de l'information, longue... Et voici la description de l'information, longue... Et voici la description de l'information, longue..."));
})