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

use RuntimeException;

/**
 * Class Authenticator
 *
 * Abstract base class for handling user authentication logic.
 * Defines the contract for authenticating users, checking administrative privileges,
 * and managing session data related to authentication.
 *
 * @package bfmw\core
 */
abstract class Authenticator
{
    /**
     * Configures secure session cookie parameters for authentication flows.
     */
    public function __construct()
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }


    /**
     * Authenticates the user.
     *
     * @return array|false Returns an array of user data on success, or false on failure.
     */
    abstract public function authenticate(): array|false;

    /**
     * Checks if the authenticated user has administrator privileges.
     *
     * @return bool True if the user is an admin, false otherwise.
     */
    abstract public function isAdmin(): bool;

    /**
     * Checks if the user is currently registered/logged in.
     *
     * @return bool True if the user is registered, false otherwise.
     */
    abstract public function isRegistered(): bool;

    /**
     * Enforces that the current session belongs to a registered user.
     *
     * Throws an exception if the user is not registered, effectively breaking
     * execution for unauthorized access.
     *
     * @throws RuntimeException If access is forbidden (user not registered).
     * @return void
     */
    public function breakSession(): void
    {
        if (!$this->isRegistered())
        {
            throw new RuntimeException("Accès interdit");
        }
    }

    /**
     * Saves the provided data into the current session.
     *
     * @param array $data An associative array of data to store in $_SESSION.
     * @return void
     */
    public function saveAsSession(array $data): void
    {
        foreach ($data as $key=>$value) {
            $_SESSION[$key] = $value;
        }
    }
}
