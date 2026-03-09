<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace b_fmw\bfmw\templating;

use bfmw\core\Comparator;
use bfmw\core\Csrf;
use Closure;
use DateTime;

/**
 * Class Templating
 *
 * Wrapper around the TemplateEngine to simplify template management.
 * Handles template initialization, file attachment, variable assignment,
 * and complex block repetition logic.
 *
 * @package bfmw\templating
 */
class Templating
{
    /**
     * Root directory for global_templates.
     * @var string
     */
    private string $template_root;
    /**
     * Instance of the template engine.
     * @var TemplateEngine
     */
    private TemplateEngine $template;
    /**
     * Unique identifier for the current template handle.
     * @var string
     */
    private string $identificatorName;

    /**
     * Templating constructor.
     *
     * Initializes the template engine and sets the main template file.
     *
     * @param string $identificatorName Unique handle name for the template.
     * @param string $filePath Path to the template file relative to the template root.
     */
    public function __construct(string $identificatorName, string $filePath, string $template_root = "templates")
    {
        $this->template_root = $template_root;
        $this->template = new TemplateEngine($this->template_root);
        $this->identificatorName = $identificatorName;
        $this->setAttachment($filePath);
    }

    /**
     * Attaches a template file to the engine.
     *
     * Verifies file existence, registers it with the engine, and assigns
     * global default variables (e.g., today's timestamp).
     *
     * @param string $filePath Path to the template file.
     * @return void
     */
    private function setAttachment(string $filePath): void
    {
        if (file_exists($this->template_root . "/" . $filePath)) {
            $this->template->setFileNames(array($this->identificatorName => $filePath));
            $today = new DateTime();
            $this->affectToHTML(array(
                "BFMW_GLOBAL_DAYTIME" => mktime(0, 0, 0, $today->format("m"), $today->format("d"), $today->format("Y"))
            ));
        }
    }

    /**
     * Assigns global variables to the template.
     *
     * @param array $var_table Associative array of variable names and values.
     * @return void
     */
    function affectToHTML(array $var_table): void
    {
        $this->template->assignVars($var_table);
    }

    /**
     * Initializes or resets a template block.
     *
     * Useful for creating empty blocks or preparing a block for iteration.
     *
     * @param string $bloc_name The name of the block to open.
     * @return void
     */
    function openBloc(string $bloc_name): void
    {
        $this->template->assignBlockVars($bloc_name, array());
    }

    /**
     * Assigns variables to a specific block iteration.
     *
     * Adds a new row of data to the specified block.
     *
     * @param string $bloc_name The name of the block.
     * @param array $var_table Associative array of variables for this block iteration.
     * @return void
     */
    function affectToBloc(string $bloc_name, array $var_table): void
    {
        $this->template->assignBlockVars($bloc_name, $var_table);
    }

    /**
     * Advanced method to populate a block with repeating data.
     *
     * Handles iteration over a data array, conditional logic via Comparator,
     * nested breaking/grouping logic via multiplicator, and custom closures.
     *
     * @param string $blocName The name of the target block.
     * @param array $data Array of data to iterate over.
     * @param Comparator|null $comparator Optional comparator for conditional value assignment (e.g., for 'selected' states).
     * @param array|null $multiplicator Optional structure for handling nested breaks/groups.
     * @param Closure|null $toDoAtEnd Optional closure to execute for each item instead of default assignment.
     * @param string|null $keyWithoutRepeat Optional key to filter out duplicate entries based on a specific column.
     * @return string|null Returns the first matched key if using a comparator, or null/string otherwise.
     */
    function affectToBlocAndRepeat(string $blocName, array $data, ?Comparator $comparator = null, ?array $multiplicator = null, ?Closure $toDoAtEnd = null, ?string $keyWithoutRepeat = null): ?string
    {
        $return = "";
        $first = "-1";
        $nonRepeatableValue = array();
        foreach ($data as $oneData) {
            if ($keyWithoutRepeat != null) {
                if (!in_array($oneData[$keyWithoutRepeat], $nonRepeatableValue)) {
                    $nonRepeatableValue[] = $oneData[$keyWithoutRepeat];
                } else {
                    continue;
                }
            }


            if ($comparator != null) {
                if ($first === "-1") {
                    $first = $oneData[$comparator->getKey()];
                }
                if ($oneData[$comparator->getKey()] == $comparator->getElementToCompare()) {
                    $return = $oneData[$comparator->getKey()];
                    $oneData[$comparator->getSelector()] = $comparator->getTrueValue();
                } else {
                    $oneData[$comparator->getSelector()] = $comparator->getFalseValue();
                }
            }
            if ($multiplicator != null) {
                foreach ($multiplicator as $breakPack => &$breakKey) {
                    if ($breakKey[0] != $oneData[$breakKey[1]]) {

                        $goClean = false;
                        foreach ($multiplicator as $cleanKey => &$clean_value) {
                            if ($cleanKey === $breakPack) {
                                $goClean = true;
                                continue;
                            }
                            if ($goClean) {
                                $clean_value[0] = -1;
                            }
                        }

                        $breakKey[0] = $oneData[$breakKey[1]];
                        $this->affectToBloc($breakPack, $oneData);
                    }
                }
            }
            if ($toDoAtEnd != null) {
                $toDoAtEnd($oneData);
            } else {
                $this->affectToBloc($blocName, $oneData);
            }
        }
        return $return === "" ? $first : $return;
    }

    /**
     * Generates and outputs (or returns) the final HTML.
     *
     * @param bool $withoutEcho If true, returns the HTML string instead of printing it.
     * @return bool|string Returns the HTML string if $withoutEcho is true, otherwise returns boolean status.
     */
    function generateCompleteHTML(bool $withoutEcho = false): bool|string
    {
        if ($withoutEcho) {
            return $this->template->assignDisplay($this->identificatorName);
        } else {
            return $this->template->display($this->identificatorName);
        }
    }
}