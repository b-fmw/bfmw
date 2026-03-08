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

/**
 * Class Comparator
 *
 * A value object used to compare a key against a target element.
 * It is typically used in templating to handle UI states (like 'selected' or 'checked' attributes)
 * by defining values for match and mismatch scenarios.
 *
 * @package bfmw\core
 */
class Comparator
{
    private string $key;
    private ?string $elementToCompare;
    private string $selector;
    private string $trueValue;
    private string $falseValue;

    /**
     * Comparator constructor.
     *
     * @param string $key The primary reference value.
     * @param string|null $elementToCompare The value to compare against the key.
     * @param string $selector The selector identifier (default: "SELECTED").
     * @param string $trueValue The string to return if the comparison matches (default: "selected").
     * @param string $falseValue The string to return if the comparison fails (default: "").
     */
    public function __construct(string $key, ?string $elementToCompare, string $selector = "SELECTED", string $trueValue = "selected", string $falseValue = "")
    {
        $this->key = $key;
        $this->elementToCompare = $elementToCompare;
        $this->selector = $selector;
        $this->trueValue = $trueValue;
        $this->falseValue = $falseValue;
    }

    /**
     * Gets the primary reference value.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Gets the value to be compared.
     *
     * @return string|null
     */
    public function getElementToCompare(): ?string
    {
        return $this->elementToCompare;
    }


    /**
     * Gets the selector identifier.
     *
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }


    /**
     * Gets the value to be used when the comparison is true.
     *
     * @return string
     */
    public function getTrueValue(): string
    {
        return $this->trueValue;
    }

    /**
     * Gets the value to be used when the comparison is false.
     *
     * @return string
     */
    public function getFalseValue(): string
    {
        return $this->falseValue;
    }


}