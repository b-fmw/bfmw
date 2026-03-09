<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace b_fmw\bfmw\generators;

use bfmw\core\PageGenerator;
use bfmw\core\ParametersEncoder;
use bfmw\templating\Templating;

/**
 * Renders inline encoded-parameter fields from a shared HTML template.
 */
class ParametersGenerator extends PageGenerator
{
    private ParametersEncoder $tokenGenerator;

    /**
     * Initializes the parameters inline template renderer.
     */
    public function __construct()
    {
        parent::__construct(new Templating("template_parameters_generator", "template_parameters_generator.html","../bfmw/global_templates"));
        $this->tokenGenerator=$this->tokenGenerator??new ParametersEncoder();
    }

    /**
     * Generates an HTML fragment embedding an encoded parameter token.
     *
     * @param string $mode Template mode used to render the fragment.
     * @param array $data Data to store behind the token.
     * @param bool $oneTime Whether the token is one-time use.
     * @param int $ttlSeconds Token lifetime in seconds.
     * @return string Rendered HTML fragment.
     * @throws \Random\RandomException
     */
    public function generateInline(string $mode, array $data,bool $oneTime = true, int $ttlSeconds = 3600) : string
    {
        $this->engine->affectToHTML(array(
            "MODE" => $mode,
            "TOKEN" => $this->tokenGenerator->token($data,$ttlSeconds,$oneTime)
        ));
        return $this->engine->generateCompleteHTML(true);
    }
}
