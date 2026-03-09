<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace bfmw\generators;

use bfmw\core\Csrf;
use bfmw\core\PageGenerator;
use bfmw\templating\Templating;
use Random\RandomException;

/**
 * Renders inline CSRF fields from a shared HTML template.
 */
class CsrfGenerator extends PageGenerator
{
    private Csrf $tokenGenerator;

    /**
     * Initializes the CSRF inline template renderer.
     */
    public function __construct()
    {
        parent::__construct(new Templating("template_csrf", "template_csrf.html",__DIR__ . "/../global_templates"));
        $this->tokenGenerator=$this->tokenGenerator??new Csrf();
    }

    /**
     * Generates a CSRF HTML fragment for forms or links.
     *
     * @param string $mode Template mode used to render the fragment.
     * @param string $context Logical CSRF context identifier.
     * @param bool $oneTime Whether the generated token is one-time use.
     * @param int $ttlSeconds Token lifetime in seconds.
     * @return string Rendered HTML fragment.
     * @throws RandomException
     */
    public function generateInline(string $mode, string $context, bool $oneTime = true, int $ttlSeconds = 3600) : string
    {
        $this->engine->affectToHTML(array(
            "MODE" => $mode,
            "TOKEN" => $this->tokenGenerator->token($context,$ttlSeconds,$oneTime),
            "TOKEN_ID" => $context
        ));
        return $this->engine->generateCompleteHTML(true);
    }
}
