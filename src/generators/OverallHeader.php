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
use bfmw\core\Helpers;
use bfmw\core\PageGenerator;
use bfmw\core\HtmlHeadGenerator;
use bfmw\templating\Templating;

/**
 * Renders the global page header and shared front-end assets.
 */
class OverallHeader extends PageGenerator
{
    private const string PACKAGE_SOURCE_PATH = __DIR__ . '/..';
    private const string PACKAGE_ASSET_BASE_URL = '/vendor/b_fmw/bfmw/src';

    /**
     * Prepares the global HTML header with shared assets and page-specific
     * styles/scripts when available.
     *
     * @param Application $application Current application instance used to
     *                                 detect the active page.
     */
    public function __construct(Application $application, bool $withModal = true, bool $withUpdater = true)
    {
        parent::__construct(new Templating("overall_header", "overall_header.html", self::PACKAGE_SOURCE_PATH . '/global_templates'));

        if (isset($_SESSION[$application->sessionPage])) {
            $nom_fichier = "css/specific_" . strtolower($_SESSION[$application->sessionPage]) . ".css";
            if (file_exists($nom_fichier)) {
                $this->engine->openBloc("specific");
                $this->engine->affectToHTML(array(
                    "SPECIFIC_STYLE" => "$nom_fichier",
                    "SPECIFIC_STYLE_AGE" => filemtime($nom_fichier)
                ));
            }

            $nom_fichier = "js/specific_" . strtolower($_SESSION[$application->sessionPage]) . ".js";
            if (file_exists($nom_fichier)) {
                $this->engine->openBloc("specific_js");
                $this->engine->affectToHTML(array(
                    "SPECIFIC_JS" => "$nom_fichier",
                    "SPECIFIC_JS_AGE" => filemtime($nom_fichier)
                ));
            }
        }

        $local_style = "css/style.css";
        $local_js = "js/main.js";

        $assetBaseUrl = rtrim(getenv('BFMW_ASSET_BASE_URL') ?: self::PACKAGE_ASSET_BASE_URL, '/');
        $stylesheetGenerator = new HtmlHeadGenerator(self::PACKAGE_SOURCE_PATH . '/css', $assetBaseUrl . '/css');
        $jsGenerator = new HtmlHeadGenerator(self::PACKAGE_SOURCE_PATH . '/js', $assetBaseUrl . '/js');

        $this->engine->affectToHTML(array(
            "FAVICON" => $application->getFavIcon(),
            "BFMW_IMAGES_BASE" => $assetBaseUrl . '/images',
            "BFMW_STYLE" => $stylesheetGenerator->generate(),
            "LOCAL_STYLE" => "$local_style?v=".filemtime($local_style),
            "BFMW_JS" => $jsGenerator->generate(),
            "LOCAL_JS" => "$local_js?v=".filemtime($local_js)
        ));

        if ($withModal) {
            $this->engine->openBloc("modal");
        }
        if ($withUpdater) {
            $this->engine->openBloc("updater");
        }

        $this->engine->affectToBlocAndRepeat("messages",$_SESSION[Helpers::ERROR_MESSAGES]??[]);

        unset($_SESSION[Helpers::ERROR_MESSAGES]);

        $this->engine->generateCompleteHTML();
    }
}
