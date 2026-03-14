<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace applicationTest;

use bfmw\Application;
use bfmw\core\Authenticator;

/**
 * Concrete application used to demonstrate framework features.
 */
class ApplicationDeTest extends Application
{
    /**
     * Executes the application lifecycle for the requested page.
     *
     * Builds the shared header and footer generators and instantiates the page
     * generator stored in the session.
     *
     * @return void
     */
    public function run(): void
    {
        $this->makeGenerator("bfmw\\generators\\OverallHeader",[$this]);
        $this->makeGenerator("applicationTest\\generators\\Menu",[$this]);
        $this->makeGenerator("applicationTest\\generators\\".$_SESSION[$this->sessionPage],[$this]);
        $this->makeGenerator("bfmw\\generators\\OverallFooter",[$this]);
    }

    /**
     * Returns the favicon path used by the demo application.
     *
     * @return string Relative URL to the favicon file.
     */
    public function getFavIcon(): string
    {
        return "../odin_v2/images/favicon.ico";
    }
}
