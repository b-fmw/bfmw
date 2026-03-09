<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */


namespace bfmw\generators;

use bfmw\Application;
use bfmw\core\PageGenerator;
use bfmw\templating\Templating;

/**
 * Renders the global page footer and closes shared resources.
 */
class OverallFooter extends PageGenerator
{
    /**
     * Renders the shared footer and terminates the database connection.
     *
     * @param Application $application Current application, used to access the
     *                                 global database helper.
     */
    public function __construct(Application $application)
    {
        parent::__construct(new Templating("overall_footer", "overall_footer.html","../bfmw/global_templates"));
        $this->engine->generateCompleteHTML();
        $application::$dataHelpers->disconnect();

    }
}
