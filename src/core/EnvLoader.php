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
 * Class EnvLoader
 *
 * Utility class responsible for loading environment variables from a file (typically .env).
 * It parses key-value pairs and populates the global $_ENV, $_SERVER arrays, and the process environment.
 *
 * @package bfmw\core
 */
final class EnvLoader
{
    /**
     * Loads the environment configuration from a specified file.
     *
     * Reads the file line by line, parses valid variable definitions, and adds them
     * to the environment if they don't already exist (unless override is enabled).
     *
     * @param string $path Path to the .env file.
     * @param bool $override Whether to overwrite existing environment variables (default: false).
     *
     * @throws RuntimeException If the file does not exist or is not readable.
     * @return void
     */
    public function load(string $path, bool $override = false): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Fichier .env introuvable ou illisible : {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                continue;
            }

            $value = $this->normalizeValue($value);

            if (!$override && (array_key_exists($key, $_ENV) || getenv($key) !== false)) {
                continue;
            }

            $_ENV[$key] = $value;

            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }

            putenv("{$key}={$value}");
        }
    }

    /**
     * Cleans and normalizes a raw value string.
     *
     * Removes surrounding whitespace and quotes (single or double) from the value.
     *
     * @param string $value The raw value string from the env file.
     * @return string The normalized value.
     */
    private function normalizeValue(string $value): string
    {
        $value = trim($value);

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }
}