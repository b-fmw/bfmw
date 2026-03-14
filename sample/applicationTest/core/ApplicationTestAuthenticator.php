<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace applicationTest\core;

use bfmw\core\Authenticator;

/**
 * Test authenticator adding mock identity data to a CAS-authenticated user.
 */
class ApplicationTestAuthenticator extends Authenticator
{
    /**
     * Performs authentication with mock user data.
     *
     * @return array|false User information array on success, or false when
     *                     authentication fails.
     */
    public function authenticate(): array|false
    {
        $result["name"] = "BOUHOURS Cédric";
        $result["status"] = "prof";
        return $result;
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function isRegistered(): bool
    {
        return true;
    }
}
