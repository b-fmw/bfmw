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

use bfmw\generators\CsrfGenerator;
use bfmw\generators\ParametersGenerator;
use bfmw\templating\Templating;

/**
 * Class PageGenerator
 *
 * Abstract base class for all page controllers/generators.
 * It holds the reference to the templating engine used to render the page.
 *
 * @package bfmw\core
 */
abstract class PageGenerator
{
    /**
     * The templating engine instance.
     * @var Templating
     */
    protected Templating $engine;

    /**
     * PageGenerator constructor.
     *
     * @param Templating $engine The templating engine instance handling the view.
     */
    public function __construct(Templating $engine)
    {
        $this->engine = $engine;
    }
}