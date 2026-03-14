<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace applicationTest\generators;

use applicationTest\ApplicationDeTest;
use bfmw\Application;
use bfmw\core\PageGenerator;
use bfmw\generators\CsrfGenerator;
use bfmw\generators\ParametersGenerator;
use bfmw\templating\Templating;
use odin_v2\repository\Etudiants;

/**
 * Generator rendering the form and CSRF demonstration page.
 */
class Forms extends PageGenerator
{
    /**
     * Builds the welcome page generator with sample student data.
     *
     * @param ApplicationDeTest $application Current application instance to
     *                                       access authenticators and session
     *                                       state.
     */
    public function __construct(ApplicationDeTest $application)
    {
        parent::__construct(new Templating("forms", "forms.html"));
        $application->getAuthenticators()->breakSession();
        $this->engine->affectToHTML(array(
            "TOKEN_CSRF" => Application::$globalHelpers->csrf->generateInline("form","takeName",false),
            "TOKEN_CSRF_PARAM" => Application::$globalHelpers->csrf->generateInline("param","modifyDescription", false),
            "TOKEN_CSRF_DELETE_LINK" => Application::$globalHelpers->csrf->generateInline("param","deleteForm", false),
            "BFMW_PARAMETER_CHECKER" => Application::$globalHelpers->paramGenerator->generateInline("line",array(
                "where" => "5",
                "field" => "FORM_NOM",
                "tag" => "form_modification"
            ),false),
            "BFMW_PARAMETER_CHECKER_DELETE" => Application::$globalHelpers->paramGenerator->generateInline("parameter",array(
                "toDelete" => "5"
            )),
            "BFMW_PARAMETER_CHECK_MODIFY_NOM" => Application::$globalHelpers->paramGenerator->generateInline("form",array(
                "toDelete" => "Example"
            )),
            "BFMW_PARAMETER_BINDING" => Application::$globalHelpers->paramGenerator->generateInline("param",array(
                "where" => "5",
                "field" => "FORM_NOM",
                "tag" => "form_binding"
            ),false),
            "BFMW_PARAMETER2_BINDING" => Application::$globalHelpers->paramGenerator->generateInline("param",array(
                "where" => "5",
                "field" => "FORM_NOM",
                "tag" => "form_check"
            ),false)
        ));
        $this->engine->generateCompleteHTML();
    }
}