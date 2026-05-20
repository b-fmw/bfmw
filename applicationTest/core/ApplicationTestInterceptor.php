<?php
/*
 *  Author: Cédric BOUHOURS
 *  This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 *  Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 *  NonCommercial — You may not use the material for commercial purposes.
 *  NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 *  No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace applicationTest\core;

use bfmw\Application;
use bfmw\core\Interceptor;
use bfmw\core\ParametersEncoder;

/**
 * Interceptor used by the demo application to process form and binding requests.
 */
class ApplicationTestInterceptor extends Interceptor
{
    /**
     * Handles regular POST requests before page routing.
     *
     * @return bool True when the request has been fully handled.
     */
    function frontInterceptor(): bool
    {
        $route = $_GET['bfmw_orig_p'] ?? null;
        switch ($route) {
            case 'Forms':
                if (isset($_POST['bfmw_orig_nom'])) {
                    Application::$globalHelpers->makeMessage("success", "Informations reçues", "C'est tout bon, ton nom est : {$_POST['bfmw_orig_nom']}");
                    Application::$globalHelpers->makeMessage("warning", "Informations reçues", "Message 2" . print_r(new ParametersEncoder()->getParams($_POST['bfmw_orig_encoded_parameters']), true));
                    return true;
                }
                if (isset($_POST['bfmw_orig_csrf_id']) && $_POST['bfmw_orig_csrf_id'] == 'deleteForm') {
                    Application::$globalHelpers->makeMessage("success", "Informations reçues", "C'est tout bon, le paramètre reçu est : " . print_r(new ParametersEncoder()->getParams($_POST['bfmw_orig_encoded_parameters']), true));
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Handles asynchronous parameter-binding requests.
     *
     * @return bool True when the binding response was sent.
     */
    function bindingInterceptor(): bool
    {
        $bindingInProgress = $_POST['bfmw_orig_bind'] ?? null;
        if ($bindingInProgress === "doing") {
            $parameters = new ParametersEncoder()->getParams($_POST['bfmw_orig_encoded_parameters']) ?? null;
            switch ($parameters['tag']) {
                case "form_modification" :
                    parent::sendBindingResponse(true, "success", "Informations reçues", "C'est tout bon, le paramètre reçu est : " . print_r($parameters, true));
                    break;
                case "form_binding" :
                    sleep(2);
                    parent::sendBindingResponse(true, "success", "Travail terminé", 'Fin du travail');
                    break;
                case "form_check" :
                    parent::sendBindingResponse(false);
                    break;
                default :
                    parent::sendBindingResponse(false);
                    break;
            }
            return true;
        }

        return false;
    }

}
