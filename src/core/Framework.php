<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */
namespace bfmw\core;

use bfmw\Application;

/**
 * Class Framework
 *
 * Core utility class responsible for the fundamental configuration of the HTTP response
 * and the security of incoming data. It handles header management and global input sanitization.
 *
 * @package bfmw\core
 */
class Framework
{
    /**
     * Sends standard HTTP headers and configures PHP runtime settings.
     *
     * Sets headers for Content-Type (UTF-8), Content-Security-Policy, and various
     * X-* security headers to mitigate common web attacks (clickjacking, sniffing, XSS).
     * Also configures error logging, memory limits, and database error reporting.
     *
     * @return void
     */
    public function sendHeaders() : void
    {
        header('Content-type: text/html; charset=UTF-8');
        header("X-Frame-Options: deny");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        ini_set('error_log',getenv("ERROR_LOG_PATH")??"~/bfmw.log");
        ini_set('memory_limit',"512M");
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    /**
     * Sanitizes global input arrays.
     *
     * Processes $_GET, $_POST, $_COOKIE, and $_REQUEST recursively to apply
     * escaping (addslashes) and HTML entity encoding (htmlspecialchars) on both
     * keys and values. It also delegates to specific bfmw security helpers.
     *
     * @return void
     */
    public function sanitize() : void
    {
        $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        foreach ($process as $key => $val) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][addslashes($k)] = $v;
                    $process[] = &$process[$key][addslashes($k)];
                } else {
                    $process[$key][addslashes($k)] = addslashes($v);
                }
            }
        }
        unset($process);

        $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        foreach ($process as $key => $val) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][htmlspecialchars($k)] = $v;
                    $process[] = &$process[$key][htmlspecialchars($k)];
                } else {
                    $process[$key][htmlspecialchars($k)] = htmlspecialchars($v);
                }
            }
        }
        unset($process);

        $process = array(&$_GET, &$_POST, &$_REQUEST);
        Application::$globalHelpers->manualBfmwSecure($process);
        unset($process);
    }


}