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

final class Csrf
{
    private const SESSION_KEY = '__csrf_tokens';

    /**
     * Creates a CSRF token manager backed by session storage.
     */
    public function __construct() {}

    /**
     * Creates a CSRF token for a specific context.
     *
     * @param string $id Context identifier (for example: "delete-user").
     * @param int $ttlSeconds Token lifetime in seconds (0 means no expiration).
     * @param bool $oneTime When true, the token is consumed after validation.
     * @return string The generated token value.
     * @throws RandomException
     */
    public function token(string $id = 'default', int $ttlSeconds = 3600, bool $oneTime = true): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $token = bin2hex(random_bytes(32));

        $_SESSION[self::SESSION_KEY][$id] = [
            'token'   => $token,
            'expires' => $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0,
            'oneTime' => $oneTime,
        ];

        return $token;
    }

    /**
     * Validates a CSRF token sent through POST fields.
     *
     * @return bool True when the token is valid, false otherwise.
     */
    public function validateFromPost(): bool
    {
        $this->purgeExpired();

        $token = $_POST['bfmw_orig_csrf_token'] ?? '';
        $id    = $_POST['bfmw_orig_csrf_id'] ?? 'default';

        if (!is_string($token) || !is_string($id) || $token === '') {
            return false;
        }

        $store = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($store) || !isset($store[$id])) {
            return false;
        }

        $entry = $store[$id];
        if (!is_array($entry) || !isset($entry['token'])) {
            return false;
        }

        // Token expiration check
        $expires = (int)($entry['expires'] ?? 0);
        if ($expires > 0 && time() > $expires) {
            unset($_SESSION[self::SESSION_KEY][$id]);
            return false;
        }

        // Constant-time comparison
        $ok = hash_equals((string)$entry['token'], $token);

        // One-time token consumption
        $oneTime = (bool)($entry['oneTime'] ?? true);
        if ($ok && $oneTime) {
            unset($_SESSION[self::SESSION_KEY][$id]);
        }

        return $ok;
    }

    /**
     * Removes expired tokens from session storage.
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
}
