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

use bfmw\generators\CsrfGenerator;
use bfmw\generators\ParametersGenerator;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Helpers
 *
 * General utility class providing various helper methods for data security,
 * numeric conversion, error handling, and date/time manipulation.
 *
 * @package bfmw\core
 */
class Helpers
{
    /**
     * Year used for extracting time-only DateTime objects.
     */
    private const int MAGIC_DATE_YEAR  = 1999;
    /**
     * Month used for extracting time-only DateTime objects.
     */
    private const int MAGIC_DATE_MONTH = 11;
    /**
     * Day used for extracting time-only DateTime objects.
     */
    private const int MAGIC_DATE_DAY   = 30;

    const string ERROR_MESSAGES = "ERROR_MESSAGES";

    public CsrfGenerator $csrf;
    public ParametersGenerator $paramGenerator;

    /**
     * Initializes helper sub-components used across the application.
     */
    public function __construct()
    {
        $this->paramGenerator = new ParametersGenerator();
        $this->csrf = new CsrfGenerator();
    }


    /**
     * Secures and transforms input arrays (GET, POST, etc.).
     *
     * Iterates through the provided array of references (typically superglobals),
     * unsets original keys, and creates new keys prefixed with 'bfmw_orig_' (original value)
     * and 'bfmw_num_' (numeric value).
     *
     * @param array $process Array of references to data arrays to process.
     * @return void
     */
    public function manualBfmwSecure(array &$process) : void
    {
        foreach ($process as $key => $val) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key]["bfmw_orig_".$k] = $v;
                    $process[$key]["bfmw_num_".$k] = $this->getNumeric($v);
                    $process[] = &$process[$key]["bfmw_orig_".$k];
                    $process[] = &$process[$key]["bfmw_num_".$k];
                } else {
                    $process[$key]["bfmw_orig_".$k] = $v;
                    $process[$key]["bfmw_num_".$k] = $this->getNumeric($v);
                }
            }
        }
    }

    /**
     * Converts a value to its numeric representation if possible.
     *
     * Recursively handles arrays. Converts strings with commas to dots.
     * Returns null for non-numeric strings or "NULL".
     *
     * @param mixed $val The value to process.
     * @return mixed The numeric value, null, or array of processed values.
     */
    public function getNumeric($val) {
        if (is_array($val)) {
            foreach ($val as $key => $valeur) {
                $val[$key] = $this->getNumeric($valeur);
            }
            return $val;
        }
        if (is_numeric(str_replace(",",".",$val))) {
            $val = str_replace(",",".",$val);
            return $val;
        }
        if ($val === "NULL") {
            return null;
        }
        return null;
    }

    /**
     * Adds a secured field to a data array.
     *
     * Adds 'bfmw_orig_[field]' and 'bfmw_num_[field]' to the array.
     *
     * @param array $data Reference to the array to modify.
     * @param string $field The base field name.
     * @param mixed $value The value to assign.
     * @return void
     */
    public function bfmwAddSecured(array &$data,string $field,$value) {
        $data["bfmw_orig_".$field] = $value;
        $data["bfmw_num_".$field] = $this->getNumeric($value);
    }

    /**
     * Registers an error message in the global $_SESSION['ERROR_MESSAGES'] array.
     *
     * @param string $messageType Error code or type (e.g., "error", "success", "info", "warning").
     * @param string $titre Error title.
     * @param string $message Error description.
     * @return void
     */
    public function makeMessage($messageType, $titre, $message) : void
    {
        $_SESSION[self::ERROR_MESSAGES][] = array(
            "BFMW_MESSAGE_TYPE" => $messageType,
            "BFMW_MESSAGE_TITLE" => $titre,
            "BFMW_MESSAGE" => $message
        );

    }

    /**
     * Checks if any errors have been registered.
     *
     * Checks $_SESSION['ERROR_MESSAGES'].
     *
     * @return bool True if errors exist, false otherwise.
     */
    public function isErreur() : bool
    {
        return (count($_SESSION[self::ERROR_MESSAGES]) > 0);
    }

    /**
     * Formats a DateTime object into a localized string.
     *
     * Uses IntlDateFormatter with French locale by default.
     *
     * @param DateTimeInterface $date The date object to format.
     * @param string $pattern The ICU date format pattern (default: "EEEE d MMMM yyyy").
     * @return string The formatted date string.
     */
    public function formatDate(DateTimeInterface $date, string $pattern = "EEEE d MMMM yyyy"): string
    {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            $date->getTimezone()->getName(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        return $formatter->format($date);
    }

    /**
     * Calculates age based on a date of birth.
     *
     * @param string $dateOfBirth Date of birth in 'Y-m-d' format.
     * @return int The calculated age in years.
     * @throws InvalidArgumentException If the date format is invalid or in the future.
     */
    public function computeAge(string $dateOfBirth): int
    {
        $dn = DateTime::createFromFormat('Y-m-d', $dateOfBirth);

        if (!$dn || $dn->format('Y-m-d') !== $dateOfBirth) {
            throw new InvalidArgumentException("Date de naissance invalide : $dateOfBirth");
        }

        $today = new DateTime('today');

        if ($dn > $today) {
            throw new InvalidArgumentException("La date de naissance est dans le futur.");
        }

        return $dn->diff($today)->y;
    }

    /**
     * Parses a date string or object into a DateTimeImmutable instance at 00:00:00.
     *
     * Supports 'd/m/Y' and 'Y-m-d' string formats.
     *
     * @param string|DateTimeInterface $date The date to parse.
     * @return DateTimeImmutable The parsed date at start of day.
     * @throws InvalidArgumentException If the format is not supported.
     * @throws DateInvalidTimeZoneException
     */
    public function parseDate(string|DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        }

        $date  = trim($date);
        $tz    = new DateTimeZone(date_default_timezone_get());

        $formats = ['d/m/Y', 'Y-m-d'];

        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $date, $tz);
            if ($dt instanceof DateTimeImmutable && $dt->format($format) === $date) {
                return $dt->setTime(0, 0, 0);
            }
        }

        throw new InvalidArgumentException("Date invalide ou format non supporté : {$date}");
    }

    /**
     * Parses a date and returns it set to the end of the day (23:59:59).
     *
     * @param string|DateTimeInterface $date The date to parse.
     * @return DateTimeImmutable The parsed date at end of day.
     * @throws DateInvalidTimeZoneException
     * @throws InvalidArgumentException
     */
    public function parseDateEndOfDay(string|DateTimeInterface $date): DateTimeImmutable
    {
        return $this->parseDate($date)->setTime(23, 59, 59);
    }

    /**
     * Parses a time string into hours and minutes.
     *
     * Supports 'HHhMM', 'HH:MM' formats or a custom separator.
     *
     * @param string $time The time string.
     * @param string|null $separator Custom separator (optional).
     * @return array An array containing [hour (int), minute (int)].
     * @throws InvalidArgumentException If the time is invalid.
     */
    public function parseTime(string $time, ?string $separator = null): array
    {
        $time = trim($time);

        if ($separator === null) {
            if (str_contains($time, 'h')) {
                [$hour, $minute] = explode('h', $time, 2);
            } else {
                [$hour, $minute] = explode(':', $time, 2);
            }
        } else {
            [$hour, $minute] = explode($separator, $time, 2);
        }

        $hour   = (int) $hour;
        $minute = (int) $minute;

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw new InvalidArgumentException("Heure invalide : {$time}");
        }

        return [$hour, $minute];
    }

    /**
     * Creates a DateTimeImmutable from a combined date and time string.
     *
     * Handles 'DateTTime' (ISO) or 'Date Time' formats.
     *
     * @param string $combined The combined date-time string.
     * @return DateTimeImmutable The resulting DateTime object.
     * @throws DateInvalidTimeZoneException
     * @throws InvalidArgumentException
     */
    public function dateTimeFromCombined(string $combined): DateTimeImmutable
    {
        $combined = trim($combined);

        if (str_contains($combined, 'T')) {
            [$date, $time] = explode('T', $combined, 2);
        } else {
            [$date, $time] = preg_split('/\s+/', $combined, 2);
        }

        $dtDate          = $this->parseDate($date);
        [$hour, $minute] = $this->parseTime($time);

        return $dtDate->setTime($hour, $minute, 0);
    }

    /**
     * Creates a DateTimeImmutable from a timestamp, preserving only the time.
     *
     * The date part is set to a fixed "magic" date (1999-11-30).
     *
     * @param int $timestamp The Unix timestamp.
     * @return DateTimeImmutable
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function extractTimeFromTimestamp(int $timestamp): DateTimeImmutable
    {
        $tz = new DateTimeZone(date_default_timezone_get());

        $dt = new DateTimeImmutable('@' . $timestamp)->setTimezone($tz);

        return $dt
            ->setDate(self::MAGIC_DATE_YEAR, self::MAGIC_DATE_MONTH, self::MAGIC_DATE_DAY);
    }

    /**
     * Creates a DateTimeImmutable from a timestamp, preserving only the date.
     *
     * The time part is reset to 00:00:00.
     *
     * @param int $timestamp The Unix timestamp.
     * @return DateTimeImmutable
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function extractDateFromTimestamp(int $timestamp): DateTimeImmutable
    {
        $tz = new DateTimeZone(date_default_timezone_get());

        $dt = new DateTimeImmutable('@' . $timestamp)->setTimezone($tz);

        return $dt->setTime(0, 0, 0);
    }

    /**
     * Redirects to the current URL after removing all query-string parameters,
     * except the ones explicitly provided.
     *
     * @param string ...$paramsToKeep Raw parameter names to keep in the query string.
     * @return void
     */
    public function redirectWithoutQueryParam(string ...$paramsToKeep): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $parts = parse_url($uri);
        $path  = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        if ($query === '') {
            return;
        }

        $segments = explode('&', $query);
        $kept = [];
        $allowedParams = array_flip($paramsToKeep);

        foreach ($segments as $seg) {
            if ($seg === '') continue;

            $rawKey = explode('=', $seg, 2)[0];
            $decodedKey = urldecode($rawKey);

            if (isset($allowedParams[$rawKey]) || isset($allowedParams[$decodedKey])) {
                $kept[] = $seg;
            }
        }

        $newQuery = implode('&', $kept);
        $newUri   = $path . ($newQuery !== '' ? ('?' . $newQuery) : '');

        if (headers_sent()) {
            throw new RuntimeException("Impossible de rediriger : les headers ont déjà été envoyés.");
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $code   = ($method === 'POST') ? 303 : 302;

        header('Location: ' . $newUri, true, $code);
        exit;
    }


}
