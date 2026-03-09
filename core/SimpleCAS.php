<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace b_fmw\bfmw\core;

use RuntimeException;
use SimpleXMLElement;

/**
 * Minimal CAS client handling login redirection and service ticket validation.
 */
class SimpleCAS
{
    private const CAS_NS = 'http://www.yale.edu/tp/cas';

    /**
     * Authenticates a user through a CAS server and returns parsed CAS response data.
     *
     * Success shape:
     * [
     *   'success' => true,
     *   'service' => 'https://app/...',
     *   'ticket' => 'ST-...',
     *   'user' => 'login',
     *   'attributes' => array<string, string|array<int,string>>,
     *   'proxies' => array<int,string>,
     *   'proxyGrantingTicket' => 'PGT-...' (optional),
     *   'raw_xml' => '...'
     * ]
     */
    public function authenticate(
        string  $host,
        int     $port,
        string  $context,
        ?string $baseUrl = null,
        bool    $disableSSLValidation = false
    ): array
    {
        $currentUrl = $this->getCurrentUrl($baseUrl);
        $serviceUrl = $this->buildServiceUrlWithoutCasParams($currentUrl);

        $ticket = $_GET['bfmw_orig_ticket'] ?? $_GET['ticket'] ?? null;

        if ($ticket !== null && $ticket !== '') {
            $validateUrl = "https://{$host}:{$port}{$context}/serviceValidate"
                . "?service=" . urlencode($serviceUrl)
                . "&ticket=" . urlencode((string)$ticket);

            $contextOptions = [];
            if ($disableSSLValidation) {
                $contextOptions['ssl'] = [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ];
            }

            $response = file_get_contents($validateUrl, false, stream_context_create($contextOptions));
            if ($response === false) {
                throw new RuntimeException("Impossible de contacter le serveur CAS.");
            }

            $data = $this->parseServiceValidateResponse($response);
            $data['service'] = $serviceUrl;
            $data['ticket'] = (string)$ticket;

            if (!empty($data['success'])) {
                if ($this->requestContainsTicket()) {
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                    $_SESSION['bfmw_cas_auth_result'] = $data;
                    session_write_close();
                    header('Location: ' . $serviceUrl);
                    exit;
                }

                return $data;
            }

            $loginUrl = "https://{$host}:{$port}{$context}/login?service=" . urlencode($serviceUrl);
            header('Location: ' . $loginUrl);
            exit;
        }

        $loginUrl = "https://{$host}:{$port}{$context}/login?service=" . urlencode($serviceUrl);
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Parse CAS /serviceValidate response.
     */
    private function parseServiceValidateResponse(string $xmlString): array
    {
        $xml = new SimpleXMLElement($xmlString);
        $xml->registerXPathNamespace('cas', self::CAS_NS);

        $success = $xml->xpath('/cas:serviceResponse/cas:authenticationSuccess')[0] ?? null;
        if ($success instanceof SimpleXMLElement) {
            $userNode = $xml->xpath('/cas:serviceResponse/cas:authenticationSuccess/cas:user')[0] ?? null;
            $user = $userNode ? (string)$userNode : '';

            $pgtNode = $xml->xpath('/cas:serviceResponse/cas:authenticationSuccess/cas:proxyGrantingTicket')[0] ?? null;
            $pgt = $pgtNode ? (string)$pgtNode : null;

            $proxies = [];
            foreach ($xml->xpath('/cas:serviceResponse/cas:authenticationSuccess/cas:proxies/cas:proxy') ?: [] as $p) {
                $proxies[] = (string)$p;
            }

            $attributes = [];
            foreach ($xml->xpath('/cas:serviceResponse/cas:authenticationSuccess/cas:attributes/*') ?: [] as $a) {
                $name = $a->getName();
                $value = (string)$a;

                if (!array_key_exists($name, $attributes)) {
                    $attributes[$name] = $value;
                } else {
                    $attributes[$name] = (array)$attributes[$name];
                    $attributes[$name][] = $value;
                }
            }

            $known = ['user', 'proxyGrantingTicket', 'proxies', 'attributes'];
            foreach (($success->children(self::CAS_NS) ?: []) as $child) {
                $name = $child->getName();
                if (in_array($name, $known, true)) {
                    continue;
                }
                $value = (string)$child;
                if ($value === '') {
                    continue;
                }

                if (!array_key_exists($name, $attributes)) {
                    $attributes[$name] = $value;
                } else {
                    $attributes[$name] = (array)$attributes[$name];
                    $attributes[$name][] = $value;
                }
            }

            $out = [
                'success' => true,
                'user' => $user,
                'attributes' => $attributes,
                'proxies' => $proxies,
                'raw_xml' => $xmlString,
            ];

            if ($pgt !== null && $pgt !== '') {
                $out['proxyGrantingTicket'] = $pgt;
            }

            return $out;
        }

        $failure = $xml->xpath('/cas:serviceResponse/cas:authenticationFailure')[0] ?? null;
        if ($failure instanceof SimpleXMLElement) {
            $code = (string)($failure['code'] ?? '');
            $message = trim((string)$failure);

            return [
                'success' => false,
                'failure' => [
                    'code' => $code,
                    'message' => $message,
                ],
                'raw_xml' => $xmlString,
            ];
        }

        return [
            'success' => false,
            'failure' => [
                'code' => 'INVALID_RESPONSE',
                'message' => 'Réponse CAS inattendue (ni success ni failure).',
            ],
            'raw_xml' => $xmlString,
        ];
    }

    /**
     * Rebuilds the current request URL including query parameters.
     */
    private function getCurrentUrl(?string $baseUrl = null): string
    {
        if ($baseUrl) {
            $parts = parse_url($baseUrl);
            $scheme = $parts['scheme'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http');
            $host = $parts['host'] ?? $_SERVER['HTTP_HOST'];
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        } else {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $port = '';
        }

        return $scheme . '://' . $host . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     * Build the "service" URL by removing CAS params (ticket...) from the query string,
     * WITHOUT changing what the browser displays.
     */
    private function buildServiceUrlWithoutCasParams(string $url): string
    {
        $p = parse_url($url);

        $query = [];
        if (!empty($p['query'])) {
            parse_str($p['query'], $query);
            unset($query['ticket'], $query['bfmw_orig_ticket']);
        }

        $scheme = $p['scheme'] ?? 'https';
        $host = $p['host'] ?? '';
        $port = isset($p['port']) ? ':' . $p['port'] : '';
        $path = $p['path'] ?? '/';
        $qs = $query ? ('?' . http_build_query($query)) : '';
        $frag = isset($p['fragment']) ? ('#' . $p['fragment']) : '';

        return "{$scheme}://{$host}{$port}{$path}{$qs}{$frag}";
    }

    /**
     * Checks whether the incoming request URI still contains a CAS ticket parameter.
     */
    private function requestContainsTicket(): bool
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return false;
        }

        parse_str($query, $params);

        return isset($params['ticket']) && $params['ticket'] !== '';
    }
}
