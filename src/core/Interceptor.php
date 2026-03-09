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

/**
 * Class Interceptor
 *
 * Abstract base class for implementing interception logic before the application routing process.
 * Allows defining custom behaviors that run before the standard page routing mechanism.
 *
 * @package bfmw\core
 */
class Interceptor
{
    /**
     * Interceptor meant for frontend-specific logic.
     *
     * Override this method to implement custom logic that should run before routing.
     * If it returns true, the standard routing process is halted.
     *
     * @return bool Returns true to stop further routing, false to continue.
     */
    function frontInterceptor() : bool {
        return false;
    }

    /**
     * Interceptor meant for data binding or preparation logic.
     *
     * Override this method to implement custom binding logic.
     * If it returns true, the standard routing process is halted.
     *
     * @return bool Returns true to stop further routing, false to continue.
     */
    function bindingInterceptor() : bool {
        return false;
    }

    /**
     * Sends a standardized JSON payload for binding requests.
     *
     * @param bool $success Processing status (true for success, false for failure).
     * @param string $messageType Type of message (e.g., "info", "warning", "error"). Empty for no message.
     * @param string $messageTitle Title of the message.
     * @param string $message Detailed message content.
     */
    protected function sendBindingResponse(bool $success, string $messageType = "", string $messageTitle = "", string $message = ""): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'type' => $messageType,
            'title' => $messageTitle,
            'message' => $message
        ]);
    }
}