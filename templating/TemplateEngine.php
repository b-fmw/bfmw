<?php
/**
 *
 * @package phpBB3
 * @version $Id: TemplateEngine.php 8943 2008-09-26 13:09:56Z acydburn $
 * @copyright (c) 2005 phpBB Group, sections (c) 2001 ispi of Lincoln Inc
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace b_fmw\bfmw\templating;

/**
 * Base TemplateEngine class.
 * @package phpBB3
 */
class TemplateEngine
{
    /** variable that holds all the data we'll be substituting into
     * the compiled global_templates. Takes form:
     * --> $this->_tpldata[block][iteration#][child][iteration#][child2][iteration#][variablename] == value
     * if it's a root-level variable, it'll be like this:
     * --> $this->_tpldata[.][0][varname] == value
     */
    private $_tpldata = array('.' => array(0 => array()));
    private $_rootref;

    // Root dir and hash of filenames for each templating handle.
    private $root = '';
    private $cachePath = '';
    private $files = array();
    private $fileName = array();
    private $compiledCode = array();

    // Various storage arrays(functions_template)
    private $blockNames = array();
    private $blockElseLevel = array();

    // ***** addon *******
    private $lang = NULL;
    const PHPEX = 'php';


    /**
     * Set templating location
     * @access public
     */
    public function __construct($templatePath = 'Template', $cachePath = 'cache', &$lang = NULL)
    {
        if (file_exists($templatePath)) {
            $this->root = $templatePath;
            $this->cachePath = $cachePath . '/tpl_';
            $this->lang = &$lang;
        } else
            trigger_error('TemplateEngine path could not be found: ' . $templatePath, E_USER_ERROR);

        $this->_rootref = &$this->_tpldata['.'][0];
    }

    /**
     * Set custom templating location (able to use directory outside of phpBB)
     * @access public
     */
    function setCustomTemplate(string $templatePath,string $cachePath) : bool
    {
        $this->root = $templatePath;
        $this->cachePath = $cachePath . '/tpl_';

        return true;
    }

    /**
     * addon
     * Set custom language variable
     * @access public
     */
    function setLanguageVar(string &$lang): void
    {
        $this->lang = &$lang;
    }

    /**
     * Sets the templating filenames for handles. $filename_array
     * should be a hash of handle => filename pairs.
     * @access public
     */
    public function setFileNames(array $fileNameArray): bool
    {
        if (!is_array($fileNameArray))
            return false;

        foreach ($fileNameArray as $handle => $filename) {
            if (empty($filename))
                trigger_error("templating->set_filenames: Empty filename specified for $handle", E_USER_ERROR);

            $this->fileName[$handle] = $filename;
            $this->files[$handle] = $this->root . '/' . $filename;
        }

        return true;
    }

    /**
     * Destroy templating data set
     * @access public
     */
    public function destroy() : void
    {
        $this->_tpldata = array('.' => array(0 => array()));
    }

    /**
     * Reset/empty complete block
     * @access public
     */
    public function destroyBlockVars(string $blockName) : bool
    {
        if (strpos($blockName, '.') !== false) {
            // Nested block.
            $blocks = explode('.', $blockName);
            $blockcount = sizeof($blocks) - 1;

            $str = &$this->_tpldata;
            for ($i = 0; $i < $blockcount; $i++) {
                $str = &$str[$blocks[$i]];
                $str = &$str[sizeof($str) - 1];
            }

            unset($str[$blocks[$blockcount]]);
        } else {
            // Top-level block.
            unset($this->_tpldata[$blockName]);
        }

        return true;
    }

    /**
     * Display handle
     * @access public
     */
    public function display(string $handle,bool $includeOnce = true) : bool
    {
        if ($filename = $this->tplLoad($handle))
            ($includeOnce) ? include_once($filename) : include($filename);
        else
            eval(' ?>' . $this->compiledCode[$handle] . '<?php ');

        return true;
    }

    /**
     * Display the handle and assign the output to a templating variable or return the compiled result.
     * @access public
     */
    public function assignDisplay(string $handle,string $templateVar = '',bool $returnContent = true,bool $includeOnce = false): bool|string
    {
        ob_start();
        $this->display($handle, $includeOnce);
        $contents = ob_get_clean();

        if ($returnContent)
            return $contents;

        $this->assignVar($templateVar, $contents);

        return true;
    }

    /**
     * Load a compiled templating if possible, if not, recompile it
     * @access private
     */
    private function tplLoad(string &$handle) : string | bool
    {
        $filename = $this->cachePath . str_replace('/', '.', $this->fileName[$handle]) . '.' . self::PHPEX;

        if (!file_exists($filename) || @filesize($filename) === 0)
            $recompile = true;
        else
            $recompile = @filemtime($filename) < filemtime($this->files[$handle]);

        // Recompile page if the original templating is newer, otherwise load the compiled version
        if (!$recompile)
            return $filename;

        // If we don't have a file assigned to this handle, die.
        if (!isset($this->files[$handle]))
            trigger_error("templating->_tpl_load(): No file specified for handle $handle", E_USER_ERROR);

        $this->tplLoadFile($handle);
        return false;
    }

    /**
     * Assign key variable pairs from an array
     * @access public
     */
    public function assignVars(array $varArray) : bool
    {
        foreach ($varArray as $key => $val)
            $this->_rootref[$key] = $val;

        return true;
    }

    /**
     * Assign a single variable to a single key
     * @access public
     */
    public function assignVar(string $varName,string $varVal) : bool
    {
        $this->_rootref[$varName] = $varVal;

        return true;
    }

    /**
     * Assign key variable pairs from an array to a specified block
     * @access public
     */
    public function assignBlockVars(string $blockName,array $varArray): bool
    {
        if (strpos($blockName, '.') !== false) {
            // Nested block.
            $blocks = explode('.', $blockName);
            $blockcount = sizeof($blocks) - 1;

            $str = &$this->_tpldata;
            for ($i = 0; $i < $blockcount; $i++) {
                $str = &$str[$blocks[$i]];
                if (!empty($str)) {
                    $str = &$str[sizeof($str) - 1];
                }
            }

            $s_row_count = isset($str[$blocks[$blockcount]]) ? sizeof($str[$blocks[$blockcount]]) : 0;
            $varArray['S_ROW_COUNT'] = $s_row_count;

            // Assign S_FIRST_ROW
            if (!$s_row_count)
                $varArray['S_FIRST_ROW'] = true;

            // Now the tricky part, we always assign S_LAST_ROW and remove the entry before
            // This is much more clever than going through the complete templating data on display (phew)
            $varArray['S_LAST_ROW'] = true;
            if ($s_row_count > 0)
                unset($str[$blocks[$blockcount]][($s_row_count - 1)]['S_LAST_ROW']);

            // Now we add the block that we're actually assigning to.
            // We're adding a new iteration to this block with the given
            // variable assignments.
            $str[$blocks[$blockcount]][] = $varArray;
        } else {
            // Top-level block.
            $s_row_count = (isset($this->_tpldata[$blockName])) ? sizeof($this->_tpldata[$blockName]) : 0;
            $varArray['S_ROW_COUNT'] = $s_row_count;

            // Assign S_FIRST_ROW
            if (!$s_row_count)
                $varArray['S_FIRST_ROW'] = true;

            // We always assign S_LAST_ROW and remove the entry before
            $varArray['S_LAST_ROW'] = true;
            if ($s_row_count > 0)
                unset($this->_tpldata[$blockName][($s_row_count - 1)]['S_LAST_ROW']);

            // Add a new iteration to this block with the variable assignments we were given.
            $this->_tpldata[$blockName][] = $varArray;
        }

        return true;
    }

    /**
     * Change already assigned key variable pair (one-dimensional - single loop entry)
     *
     * An example of how to use this function:
     * {@example alter_block_array.php}
     *
     * @param string $blockName the blockname, for example 'loop'
     * @param array $varArray the var array to insert/add or merge
     * @param mixed $key Key to search for
     *
     * array: KEY => VALUE [the key/value pair to search for within the loop to determine the correct position]
     *
     * int: Position [the position to change or insert at directly given]
     *
     * If key is false the position is set to 0
     * If key is true the position is set to the last entry
     *
     * @param string $mode Mode to execute (valid modes are 'insert' and 'change')
     *
     *    If insert, the vararray is inserted at the given position (position counting from zero).
     *    If change, the current block gets merged with the vararray (resulting in new key/value pairs be added and existing keys be replaced by the new value).
     *
     * Since counting begins by zero, inserting at the last position will result in this array: array(vararray, last positioned array)
     * and inserting at position 1 will result in this array: array(first positioned array, vararray, following vars)
     *
     * @return bool false on error, true on success
     * @access public
     */
    public function alterBlockArray(string $blockName,array $varArray,bool $key = false,string $mode = 'insert'): bool
    {
        if (strpos($blockName, '.') !== false) {
            // Nested blocks are not supported
            return false;
        }

        // Change key to zero (change first position) if false and to last position if true
        if ($key === false || $key === true) {
            $key = ($key === false) ? 0 : sizeof($this->_tpldata[$blockName]);
        }

        // Get correct position if array given
        if (is_array($key)) {
            // Search array to get correct position
            list($search_key, $search_value) = @each($key);

            $key = NULL;
            foreach ($this->_tpldata[$blockName] as $i => $val_ary) {
                if ($val_ary[$search_key] === $search_value) {
                    $key = $i;
                    break;
                }
            }

            // key/value pair not found
            if ($key === NULL)
                return false;
        }

        // Insert Block
        if ($mode == 'insert') {
            // Make sure we are not exceeding the last iteration
            if ($key >= sizeof($this->_tpldata[$blockName])) {
                $key = sizeof($this->_tpldata[$blockName]);
                unset($this->_tpldata[$blockName][($key - 1)]['S_LAST_ROW']);
                $varArray['S_LAST_ROW'] = true;
            } else if ($key === 0) {
                unset($this->_tpldata[$blockName][0]['S_FIRST_ROW']);
                $varArray['S_FIRST_ROW'] = true;
            }

            // Re-position templating blocks
            for ($i = sizeof($this->_tpldata[$blockName]); $i > $key; $i--) {
                $this->_tpldata[$blockName][$i] = $this->_tpldata[$blockName][$i - 1];
                $this->_tpldata[$blockName][$i]['S_ROW_COUNT'] = $i;
            }

            // Insert vararray at given position
            $varArray['S_ROW_COUNT'] = $key;
            $this->_tpldata[$blockName][$key] = $varArray;

            return true;
        }

        // Which block to change?
        if ($mode == 'change') {
            if ($key == sizeof($this->_tpldata[$blockName])) {
                $key--;
            }

            $this->_tpldata[$blockName][$key] = array_merge($this->_tpldata[$blockName][$key], $varArray);
            return true;
        }

        return false;
    }

    /**
     * Include a separate templating
     * @access private
     */
    private function tplInclude(string $fileName,bool $include = true): void
    {
        $handle = $fileName;
        $this->fileName[$handle] = $fileName;
        $this->files[$handle] = $this->root . '/' . $fileName;

        $fileName = $this->tplLoad($handle);

        if ($include) {
            if ($fileName) {
                include($fileName);
                return;
            }
            eval(' ?>' . $this->compiledCode[$handle] . '<?php ');
        }
    }

    /*-------------------- FUNCTIONS_TEMPLATE ---------------------------------*/
    /**
     * Load templating source from file
     * @access private
     */
    private function tplLoadFile(string $handle,bool $storeInDb = false): void
    {
        // Try and open templating for read
        if (!file_exists($this->files[$handle]))
            trigger_error("templating->_tpl_load_file(): File {$this->files[$handle]} does not exist or is empty", E_USER_ERROR);

        $this->compiledCode[$handle] = $this->compile(trim(@file_get_contents($this->files[$handle])));

        // Actually compile the code now.
        $this->compileWrite($handle, $this->compiledCode[$handle]);
    }

    /**
     * Remove any PHP tags that do not belong, these regular expressions are derived from
     * the ones that exist in zend_language_scanner.l
     * @access private
     */
    private function removePhpTags(string &$code): void
    {
        // This matches the information gathered from the internal PHP lexer
        $match = array(
            '#<([\?%])=?.*?\1>#s',
            '#<script\s+language\s*=\s*(["\']?)php\1\s*>.*?</script\s*>#s',
            '#<\?php(?:\r\n?|[ \n\t]).*?\?>#s'
        );

        $code = preg_replace($match, '', $code);
    }

    /**
     * The all seeing all doing compile method. Parts are inspired by or directly from Smarty
     * @access private
     */
    private function compile(string $code,bool $noEcho = false,string $echoVar = ''): string
    {
        if ($echoVar)
            global $$echoVar;

        // Remove any "loose" php ... we want to give admins the ability
        // to switch on/off PHP for a given templating. Allowing unchecked
        // php is a no-no. There is a potential issue here in that non-php
        // content may be removed ... however designers should use entities
        // if they wish to display < and >
        $this->removePhpTags($code);

        // Pull out all block/statement level elements and separate plain text
        preg_match_all('#<!-- PHP -->(.*?)<!-- ENDPHP -->#s', $code, $matches);
        $php_blocks = $matches[1];
        $code = preg_replace('#<!-- PHP -->.*?<!-- ENDPHP -->#s', '<!-- PHP -->', $code);

        preg_match_all('#<!-- INCLUDE ([a-zA-Z0-9\_\-\+\./]+) -->#', $code, $matches);
        $include_blocks = $matches[1];
        $code = preg_replace('#<!-- INCLUDE [a-zA-Z0-9\_\-\+\./]+ -->#', '<!-- INCLUDE -->', $code);

        preg_match_all('#<!-- INCLUDEPHP ([a-zA-Z0-9\_\-\+\./]+) -->#', $code, $matches);
        $includephp_blocks = $matches[1];
        $code = preg_replace('#<!-- INCLUDEPHP [a-zA-Z0-9\_\-\+\./]+ -->#', '<!-- INCLUDEPHP -->', $code);

        preg_match_all('#<!-- ([^<].*?) (.*?)? ?-->#', $code, $blocks, PREG_SET_ORDER);

        $text_blocks = preg_split('#<!-- [^<].*? (?:.*?)? ?-->#', $code);

        for ($i = 0, $j = sizeof($text_blocks); $i < $j; $i++) {
            $this->compileVarTags($text_blocks[$i]);
        }
        $compile_blocks = array();

        for ($curr_tb = 0, $tb_size = sizeof($blocks); $curr_tb < $tb_size; $curr_tb++) {
            $block_val = &$blocks[$curr_tb];

            switch ($block_val[1]) {
                case 'BEGIN':
                    $this->blockElseLevel[] = false;
                    $compile_blocks[] = '<?php ' . $this->compileTagBlock($block_val[2]) . ' ?>';
                    break;

                case 'BEGINELSE':
                    $this->blockElseLevel[sizeof($this->blockElseLevel) - 1] = true;
                    $compile_blocks[] = '<?php }} else { ?>';
                    break;

                case 'END':
                    array_pop($this->blockNames);
                    $compile_blocks[] = '<?php ' . ((array_pop($this->blockElseLevel)) ? '}' : '}}') . ' ?>';
                    break;

                case 'IF':
                    $compile_blocks[] = '<?php ' . $this->compileTagIf($block_val[2], false) . ' ?>';
                    break;

                case 'ELSE':
                    $compile_blocks[] = '<?php } else { ?>';
                    break;

                case 'ELSEIF':
                    $compile_blocks[] = '<?php ' . $this->compileTagIf($block_val[2], true) . ' ?>';
                    break;

                case 'ENDIF':
                    $compile_blocks[] = '<?php } ?>';
                    break;

                case 'DEFINE':
                    $compile_blocks[] = '<?php ' . $this->compileTagDefine($block_val[2], true) . ' ?>';
                    break;

                case 'UNDEFINE':
                    $compile_blocks[] = '<?php ' . $this->compileTagDefine($block_val[2], false) . ' ?>';
                    break;

                case 'INCLUDE':
                    $temp = array_shift($include_blocks);
                    $compile_blocks[] = '<?php ' . $this->compileTagInclude($temp) . ' ?>';
                    $this->tplInclude($temp, false);
                    break;

                case 'INCLUDEPHP':
                    $compile_blocks[] = ($config['tpl_allow_php']) ? '<?php ' . $this->compileTagIncludePhp(array_shift($includephp_blocks)) . ' ?>' : '';
                    break;

                case 'PHP':
                    $compile_blocks[] = ($config['tpl_allow_php']) ? '<?php ' . array_shift($php_blocks) . ' ?>' : '';
                    break;

                default:
                    $this->compileVarTags($block_val[0]);
                    $trim_check = trim($block_val[0]);
                    $compile_blocks[] = (!$noEcho) ? ((!empty($trim_check)) ? $block_val[0] : '') : ((!empty($trim_check)) ? $block_val[0] : '');
                    break;
            }
        }

        $template_php = '';
        for ($i = 0, $size = sizeof($text_blocks); $i < $size; $i++) {
            $trim_check_text = trim($text_blocks[$i]);
            $template_php .= (!$noEcho) ? (($trim_check_text != '') ? $text_blocks[$i] : '') . ((isset($compile_blocks[$i])) ? $compile_blocks[$i] : '') : (($trim_check_text != '') ? $text_blocks[$i] : '') . ((isset($compile_blocks[$i])) ? $compile_blocks[$i] : '');
        }

        // There will be a number of occasions where we switch into and out of
        // PHP mode instantaneously. Rather than "burden" the parser with this
        // we'll strip out such occurences, minimising such switching
        $template_php = str_replace(' ?><?php ', ' ', $template_php);

        return (!$noEcho) ? $template_php : "\$$echoVar .= '" . $template_php . "'";
    }

    /**
     * Compile variables
     * @access private
     */
    private function compileVarTags(string &$textBlocks): void
    {
        // change templating varrefs into PHP varrefs
        $varrefs = array();

        // This one will handle varrefs WITH namespaces
        preg_match_all('#\{((?:[a-z0-9\-_]+\.)+)(\$)?([A-Z0-9\-_]+)\}#', $textBlocks, $varrefs, PREG_SET_ORDER);

        foreach ($varrefs as $var_val) {
            $namespace = $var_val[1];
            $varname = $var_val[3];
            $new = $this->generateBlockVarRef($namespace, $varname, true, $var_val[2]);

            $textBlocks = str_replace($var_val[0], $new, $textBlocks);
        }

        // This will handle the remaining root-level varrefs
        // transform vars prefixed by L_ into their language variable pendant if nothing is set within the tpldata array
        if (strpos($textBlocks, '{L_') !== false) {
            $textBlocks = preg_replace('#\{L_([a-z0-9\-_]*)\}#is', "<?php echo ((isset(\$this->_rootref['L_\\1'])) ? \$this->_rootref['L_\\1'] : ((isset(\$this->lang['\\1'])) ? \$this->lang['\\1'] : '{ \\1 }')); ?>", $textBlocks);
        }

        // Handle addslashed language variables prefixed with LA_
        // If a templating variable already exist, it will be used in favor of it...
        if (strpos($textBlocks, '{LA_') !== false) {
            $textBlocks = preg_replace('#\{LA_([a-z0-9\-_]*)\}#is', "<?php echo ((isset(\$this->_rootref['LA_\\1'])) ? \$this->_rootref['LA_\\1'] : ((isset(\$this->_rootref['L_\\1'])) ? addslashes(\$this->_rootref['L_\\1']) : ((isset(\$this->lang['\\1'])) ? addslashes(\$this->lang['\\1']) : '{ \\1 }'))); ?>", $textBlocks);
        }

        // Handle remaining varrefs
        $textBlocks = preg_replace('#\{([a-z0-9\-_]+)\}#is', "<?php echo (isset(\$this->_rootref['\\1'])) ? \$this->_rootref['\\1'] : ''; ?>", $textBlocks);
        $textBlocks = preg_replace('#\{\$([a-z0-9\-_]+)\}#is', "<?php echo (isset(\$this->_tpldata['DEFINE']['.']['\\1'])) ? \$this->_tpldata['DEFINE']['.']['\\1'] : ''; ?>", $textBlocks);

    }

    /**
     * Compile blocks
     * @access private
     */
    private function compileTagBlock(string $tagArgs): string
    {
        $no_nesting = false;

        // Is the designer wanting to call another loop in a loop?
        if (strpos($tagArgs, '!') === 0) {
            // Count the number if ! occurrences (not allowed in vars)
            $no_nesting = substr_count($tagArgs, '!');
            $tagArgs = substr($tagArgs, $no_nesting);
        }

        // Allow for control of looping (indexes start from zero):
        // foo(2)    : Will start the loop on the 3rd entry
        // foo(-2)   : Will start the loop two entries from the end
        // foo(3,4)  : Will start the loop on the fourth entry and end it on the fifth
        // foo(3,-4) : Will start the loop on the fourth entry and end it four from last
        if (preg_match('#^([^()]*)\(([\-\d]+)(?:,([\-\d]+))?\)$#', $tagArgs, $match)) {
            $tagArgs = $match[1];

            if ($match[2] < 0)
                $loop_start = '($_' . $tagArgs . '_count ' . $match[2] . ' < 0 ? 0 : $_' . $tagArgs . '_count ' . $match[2] . ')';
            else
                $loop_start = '($_' . $tagArgs . '_count < ' . $match[2] . ' ? $_' . $tagArgs . '_count : ' . $match[2] . ')';

            if (strlen($match[3]) < 1 || $match[3] == -1)
                $loop_end = '$_' . $tagArgs . '_count';
            else if ($match[3] >= 0)
                $loop_end = '(' . ($match[3] + 1) . ' > $_' . $tagArgs . '_count ? $_' . $tagArgs . '_count : ' . ($match[3] + 1) . ')';
            else //if ($match[3] < -1)
                $loop_end = '$_' . $tagArgs . '_count' . ($match[3] + 1);
        } else {
            $loop_start = 0;
            $loop_end = '$_' . $tagArgs . '_count';
        }

        $tag_template_php = '';
        array_push($this->blockNames, $tagArgs);

        if ($no_nesting !== false) {
            // We need to implode $no_nesting times from the end...
            $block = array_slice($this->blockNames, -$no_nesting);
        } else {
            $block = $this->blockNames;
        }

        if (sizeof($block) < 2) {
            // Block is not nested.
            $tag_template_php = '$_' . $tagArgs . "_count = (isset(\$this->_tpldata['$tagArgs'])) ? sizeof(\$this->_tpldata['$tagArgs']) : 0;";
            $varref = "\$this->_tpldata['$tagArgs']";
        } else {
            // This block is nested.
            // Generate a namespace string for this block.
            $namespace = implode('.', $block);

            // Get a reference to the data array for this block that depends on the
            // current indices of all parent blocks.
            $varref = $this->generateBlockDataRef($namespace, false);

            // Create the for loop code to iterate over this block.
            $tag_template_php = '$_' . $tagArgs . '_count = (isset(' . $varref . ')) ? sizeof(' . $varref . ') : 0;';
        }

        $tag_template_php .= 'if ($_' . $tagArgs . '_count) {';
        $tag_template_php .= 'for ($_' . $tagArgs . '_i = ' . $loop_start . '; $_' . $tagArgs . '_i < ' . $loop_end . '; ++$_' . $tagArgs . '_i){';
        $tag_template_php .= '$_' . $tagArgs . '_val = &' . $varref . '[$_' . $tagArgs . '_i];';

        return $tag_template_php;
    }

    /**
     * Compile IF tags - much of this is from Smarty with
     * some adaptions for our block level methods
     * @access private
     */
    private function compileTagIf(string $tagArgs, bool $elseIf): string
    {
        // Tokenize args for 'if' tag.
        preg_match_all('/(?:
			"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"         |
			\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'     |
			[(),]                                  |
			[^\s(),]+)/x', $tagArgs, $match);

        $tokens = $match[0];
        $is_arg_stack = array();

        for ($i = 0, $size = sizeof($tokens); $i < $size; $i++) {
            $token = &$tokens[$i];

            switch ($token) {
                case '!==':
                case '===':
                case '<<':
                case '>>':
                case '|':
                case '^':
                case '&':
                case '~':
                case ')':
                case ',':
                case '+':
                case '-':
                case '*':
                case '/':
                case '@':
                    break;

                case '==':
                case 'eq':
                    $token = '==';
                    break;

                case '!=':
                case '<>':
                case 'ne':
                case 'neq':
                    $token = '!=';
                    break;

                case '<':
                case 'lt':
                    $token = '<';
                    break;

                case '<=':
                case 'le':
                case 'lte':
                    $token = '<=';
                    break;

                case '>':
                case 'gt':
                    $token = '>';
                    break;

                case '>=':
                case 'ge':
                case 'gte':
                    $token = '>=';
                    break;

                case '&&':
                case 'and':
                    $token = '&&';
                    break;

                case '||':
                case 'or':
                    $token = '||';
                    break;

                case '!':
                case 'not':
                    $token = '!';
                    break;

                case '%':
                case 'mod':
                    $token = '%';
                    break;

                case '(':
                    array_push($is_arg_stack, $i);
                    break;

                case 'is':
                    $is_arg_start = ($tokens[$i - 1] == ')') ? array_pop($is_arg_stack) : $i - 1;
                    $is_arg = implode('	', array_slice($tokens, $is_arg_start, $i - $is_arg_start));

                    $new_tokens = $this->parseIsExpr($is_arg, array_slice($tokens, $i + 1));

                    array_splice($tokens, $is_arg_start, sizeof($tokens), $new_tokens);

                    $i = $is_arg_start;

                // no break

                default:
                    if (preg_match('#^((?:[a-z0-9\-_]+\.)+)?(\$)?(?=[A-Z])([A-Z0-9\-_]+)#s', $token, $varrefs)) {
                        $token = (!empty($varrefs[1])) ? $this->generateBlockDataRef(substr($varrefs[1], 0, -1), true, $varrefs[2]) . '[\'' . $varrefs[3] . '\']' : (($varrefs[2]) ? '$this->_tpldata[\'DEFINE\'][\'.\'][\'' . $varrefs[3] . '\']' : '$this->_rootref[\'' . $varrefs[3] . '\']');
                    } else if (preg_match('#^\.((?:[a-z0-9\-_]+\.?)+)$#s', $token, $varrefs)) {
                        // Allow checking if loops are set with .loopname
                        // It is also possible to check the loop count by doing <!-- IF .loopname > 1 --> for example
                        $blocks = explode('.', $varrefs[1]);

                        // If the block is nested, we have a reference that we can grab.
                        // If the block is not nested, we just go and grab the block from _tpldata
                        if (sizeof($blocks) > 1) {
                            $block = array_pop($blocks);
                            $namespace = implode('.', $blocks);
                            $varref = $this->generateBlockDataRef($namespace, true);

                            // Add the block reference for the last child.
                            $varref .= "['" . $block . "']";
                        } else {
                            $varref = '$this->_tpldata';

                            // Add the block reference for the last child.
                            $varref .= "['" . $blocks[0] . "']";
                        }
                        $token = "sizeof($varref)";
                    } else if (!empty($token)) {
                        $token = '(' . $token . ')';
                    }

                    break;
            }
        }

        // If there are no valid tokens left or only control/compare characters left, we do skip this statement
        if (!sizeof($tokens) || str_replace(array(' ', '=', '!', '<', '>', '&', '|', '%', '(', ')'), '', implode('', $tokens)) == '') {
            $tokens = array('false');
        }
        return (($elseIf) ? '} else if (' : 'if (') . (implode(' ', $tokens) . ') { ');
    }

    /**
     * Compile DEFINE tags
     * @access private
     */
    private function compileTagDefine(string $tagArgs, bool $op): string
    {
        preg_match('#^((?:[a-z0-9\-_]+\.)+)?\$(?=[A-Z])([A-Z0-9_\-]*)(?: = (\'?)([^\']*)(\'?))?$#', $tagArgs, $match);

        if (empty($match[2]) || (!isset($match[4]) && $op))
            return '';

        if (!$op)
            return 'unset(' . (($match[1]) ? $this->generateBlockDataRef(substr($match[1], 0, -1), true, true) . '[\'' . $match[2] . '\']' : '$this->_tpldata[\'DEFINE\'][\'.\'][\'' . $match[2] . '\']') . ');';

        // Are we a string?
        if ($match[3] && $match[5]) {
            $match[4] = str_replace(array('\\\'', '\\\\', '\''), array('\'', '\\', '\\\''), $match[4]);

            // Compile reference, we allow templating variables in defines...
            $match[4] = $this->compile($match[4]);

            // Now replace the php code
            $match[4] = "'" . str_replace(array('<?php echo ', '; ?>'), array("' . ", " . '"), $match[4]) . "'";
        } else {
            preg_match('#true|false|\.#i', $match[4], $type);

            switch (strtolower($type[0])) {
                case 'true':
                case 'false':
                    $match[4] = strtoupper($match[4]);
                    break;

                case '.':
                    $match[4] = doubleval($match[4]);
                    break;

                default:
                    $match[4] = intval($match[4]);
                    break;
            }
        }

        return (($match[1]) ? $this->generateBlockDataRef(substr($match[1], 0, -1), true, true) . '[\'' . $match[2] . '\']' : '$this->_tpldata[\'DEFINE\'][\'.\'][\'' . $match[2] . '\']') . ' = ' . $match[4] . ';';
    }

    /**
     * Compile INCLUDE tag
     * @access private
     */
    private function compileTagInclude(string $tagArgs) : string
    {
        return "\$this->_tpl_include('$tagArgs');";
    }

    /**
     * Compile INCLUDE_PHP tag
     * @access private
     */
    private function compileTagIncludePhp(string $tagArgs) : string
    {
        return "include('" . $tagArgs . "');";
    }

    /**
     * parse expression
     * This is from Smarty
     * @access private
     */
    private function parseIsExpr(bool $isArg,array $tokens) : array
    {
        $expr_end = 0;
        $negate_expr = false;
        $expr = "";

        if (($first_token = array_shift($tokens)) == 'not') {
            $negate_expr = true;
            $expr_type = array_shift($tokens);
        } else {
            $expr_type = $first_token;
        }

        switch ($expr_type) {
            case 'even':
                if (@$tokens[$expr_end] == 'by') {
                    $expr_end++;
                    $expr_arg = $tokens[$expr_end++];
                    $expr = "!(($isArg / $expr_arg) % $expr_arg)";
                } else {
                    $expr = "!($isArg & 1)";
                }
                break;

            case 'odd':
                if (@$tokens[$expr_end] == 'by') {
                    $expr_end++;
                    $expr_arg = $tokens[$expr_end++];
                    $expr = "(($isArg / $expr_arg) % $expr_arg)";
                } else {
                    $expr = "($isArg & 1)";
                }
                break;

            case 'div':
                if (@$tokens[$expr_end] == 'by') {
                    $expr_end++;
                    $expr_arg = $tokens[$expr_end++];
                    $expr = "!($isArg % $expr_arg)";
                }
                break;
        }

        if ($negate_expr) {
            $expr = "!($expr)";
        }

        array_splice($tokens, 0, $expr_end, $expr);

        return $tokens;
    }

    /**
     * Generates a reference to the given variable inside the given (possibly nested)
     * block namespace. This is a string of the form:
     * ' . $this->_tpldata['parent'][$_parent_i]['$child1'][$_child1_i]['$child2'][$_child2_i]...['varname'] . '
     * It's ready to be inserted into an "echo" line in one of the global_templates.
     * NOTE: expects a trailing "." on the namespace.
     * @access private
     */
    private function generateBlockVarRef(string $nameSpace,string  $varName,bool $echo = true,bool $defOp = false): string
    {
        // Strip the trailing period.
        $nameSpace = substr($nameSpace, 0, -1);

        // Get a reference to the data block for this namespace.
        $varref = $this->generateBlockDataRef($nameSpace, true, $defOp);
        // Prepend the necessary code to stick this in an echo line.

        // Append the variable reference.
        $varref .= "['$varName']";
        $varref = ($echo) ? "<?php echo $varref; ?>" : ((isset($varref)) ? $varref : '');

        return $varref;
    }

    /**
     * Generates a reference to the array of data values for the given
     * (possibly nested) block namespace. This is a string of the form:
     * $this->_tpldata['parent'][$_parent_i]['$child1'][$_child1_i]['$child2'][$_child2_i]...['$childN']
     *
     * If $include_last_iterator is true, then [$_childN_i] will be appended to the form shown above.
     * NOTE: does not expect a trailing "." on the blockname.
     * @access private
     */
    private function generateBlockDataRef(string $blockName, bool $includeLastIterator, bool $defOp = false): string
    {
        // Get an array of the blocks involved.
        $blocks = explode('.', $blockName);
        $blockcount = sizeof($blocks) - 1;

        // DEFINE is not an element of any referenced variable, we must use _tpldata to access it
        if ($defOp) {
            $varref = '$this->_tpldata[\'DEFINE\']';
            // Build up the string with everything but the last child.
            for ($i = 0; $i < $blockcount; $i++) {
                $varref .= "['" . $blocks[$i] . "'][\$_" . $blocks[$i] . '_i]';
            }
            // Add the block reference for the last child.
            $varref .= "['" . $blocks[$blockcount] . "']";
            // Add the iterator for the last child if requried.
            if ($includeLastIterator) {
                $varref .= '[$_' . $blocks[$blockcount] . '_i]';
            }
            return $varref;
        } else if ($includeLastIterator) {
            return '$_' . $blocks[$blockcount] . '_val';
        } else {
            return '$_' . $blocks[$blockcount - 1] . '_val[\'' . $blocks[$blockcount] . '\']';
        }
    }

    /**
     * Write compiled file to cache directory
     * @access private
     */
    private function compileWrite(string $handle, string $data): void
    {
        $filename = $this->cachePath . str_replace('/', '.', $this->fileName[$handle]) . '.' . self::PHPEX;

        if (file_exists($filename)) {
            if ($fp = @fopen($filename, 'wb')) {
                @flock($fp, LOCK_EX);
                @fwrite($fp, $data);
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }
}
