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

use Random\RandomException;

final class ParametersEncoder
{
    private const SESSION_KEY = '__params_encoder_tokens';

    /**
     * Creates a token associated with a parameter payload.
     *
     * @param array $params Parameters to store in session.
     * @param int $ttlSeconds Token lifetime in seconds (0 means no expiration).
     * @param bool $oneTime When true, token is consumed after first read.
     * @return string Generated token.
     * @throws RandomException
     */
    public function token(array $params, int $ttlSeconds = 3600, bool $oneTime = true): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $token = bin2hex(random_bytes(32));

        $_SESSION[self::SESSION_KEY][$token] = [
            'parameters'   => $params,
            'expires' => $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0,
            'oneTime' => $oneTime,
        ];

        return $token;
    }

    /**
     * Resolves a token back to its stored parameter payload.
     *
     * @param string $token Token to resolve.
     * @return array|null Stored parameters, or null if token is invalid/expired.
     */
    public function getParams(string $token): ?array
    {
        $this->purgeExpired();

        if ($token === '') {
            return null;
        }

        $store = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($store) || !isset($store[$token])) {
            return null;
        }

        $entry = $store[$token];
        if (!is_array($entry) || !isset($entry['parameters'])) {
            return null;
        }

        $expires = (int)($entry['expires'] ?? 0);
        if ($expires > 0 && time() > $expires) {
            unset($_SESSION[self::SESSION_KEY][$token]);
            return null;
        }

        if ($entry['oneTime'] ?? true) {
            unset($_SESSION[self::SESSION_KEY][$token]);
        }

        return $entry['parameters'];
    }

    /**
     * Removes expired parameter tokens from session storage.
     */
    private function purgeExpired(): void
    {
        $store = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($store)) return;

        $now = time();
        foreach ($store as $id => $entry) {
            $expires = (int)($entry['expires'] ?? 0);
            if ($expires > 0 && $now > $expires) {
                unset($_SESSION[self::SESSION_KEY][$id]);
            }
        }
    }

    /**
     * Clears all stored parameter tokens.
     */
    public function cleanAllParameters(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
