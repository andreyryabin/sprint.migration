<?php
/**
 * Utility for printing tables from commandline scripts.
 *
 * PHP versions 5 and 7
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * o Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote products
 *   derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Console
 * @package   Console_Table
 * @author    Richard Heyes <richard@phpguru.org>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2002-2005 Richard Heyes
 * @copyright 2006-2008 Jan Schneider
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Console_Table
 */

/**
 * The main class.
 *
 * @category Console
 * @package  Console_Table
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link     http://pear.php.net/package/Console_Table
 */

/**
 * Добавил в неймспейс проекта
 * Перенес константы внутрь класса
 * Убрал возможность цветого оформления
 * Помолился богам опенсорса
 *
 * @category Sprint\Migration
 * @package  ConsoleGrid
 * @author   Andrey Ryabin <andreyryabin@yandex.ru>
 */

namespace Sprint\Migration;

class ConsoleGrid
{
    const CONSOLE_TABLE_HORIZONTAL_RULE = 1;
    const CONSOLE_TABLE_ALIGN_LEFT      = -1;
    const CONSOLE_TABLE_ALIGN_CENTER    = 0;
    const CONSOLE_TABLE_ALIGN_RIGHT     = 1;
    const CONSOLE_TABLE_BORDER_ASCII    = -1;
    /**
     * The table headers.
     *
     * @var array
     */
    var $_headers = [];
    /**
     * The data of the table.
     *
     * @var array
     */
    var $_data = [];
    /**
     * The maximum number of columns in a row.
     *
     * @var integer
     */
    var $_max_cols = 0;
    /**
     * The maximum number of rows in the table.
     *
     * @var integer
     */
    var $_max_rows = 0;
    /**
     * Lengths of the columns, calculated when rows are added to the table.
     *
     * @var array
     */
    var $_cell_lengths = [];
    /**
     * Heights of the rows.
     *
     * @var array
     */
    var $_row_heights = [];
    /**
     * How many spaces to use to pad the table.
     *
     * @var integer
     */
    var $_padding = 1;
    /**
     * Columns to calculate totals for.
     *
     * @var array
     */
    var $_calculateTotals;
    /**
     * Alignment of the columns.
     *
     * @var array
     */
    var $_col_align = [];
    /**
     * Default alignment of columns.
     *
     * @var integer
     */
    var $_defaultAlign;
    /**
     * Character set of the data.
     *
     * @var string
     */
    var $_charset = 'utf-8';
    /**
     * Border characters.
     * Allowed keys:
     * - intersection - intersection ("+")
     * - horizontal - horizontal rule character ("-")
     * - vertical - vertical rule character ("|")
     *
     * @var array
     */
    var $_border = [
        'intersection' => '+',
        'horizontal'   => '-',
        'vertical'     => '|',
    ];
    /**
     * If borders are shown or not
     * Allowed keys: top, right, bottom, left, inner: true and false
     *
     * @var array
     */
    var $_borderVisibility = [
        'top'    => true,
        'right'  => true,
        'bottom' => true,
        'left'   => true,
        'inner'  => true,
    ];

    /**
     * Constructor.
     *
     * @param integer $align   Default alignment. One of
     *                         self::CONSOLE_TABLE_ALIGN_LEFT,
     *                         self::CONSOLE_TABLE_ALIGN_CENTER or
     *                         self::CONSOLE_TABLE_ALIGN_RIGHT.
     * @param int     $border  The character used for table borders or
     *                         self::CONSOLE_TABLE_BORDER_ASCII.
     * @param integer $padding How many spaces to use to pad the table.
     * @param string  $charset A charset supported by the mbstring PHP
     *                         extension.
     */
    function __construct(
        $align = self::CONSOLE_TABLE_ALIGN_LEFT,
        $border = self::CONSOLE_TABLE_BORDER_ASCII,
        $padding = 1,
        $charset = null
    ) {
        $this->_defaultAlign = $align;
        $this->setBorder($border);
        $this->_padding = $padding;
        if (!empty($charset)) {
            $this->setCharset($charset);
        }
    }

    /**
     * Sets the charset of the provided table data.
     *
     * @param string $charset A charset supported by the mbstring PHP
     *                        extension.
     *
     * @return void
     */
    function setCharset($charset)
    {
        $locale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'en_US');
        $this->_charset = strtolower($charset);
        setlocale(LC_CTYPE, $locale);
    }

    /**
     * Set the table border settings
     *
     * Border definition modes:
     * - self::CONSOLE_TABLE_BORDER_ASCII: Default border with +, - and |
     * - array with keys "intersection", "horizontal" and "vertical"
     * - single character string that sets all three of the array keys
     *
     * @param mixed $border Border definition
     *
     * @return void
     * @see $_border
     */
    function setBorder($border)
    {
        $intersection = '';
        $horizontal = '';
        $vertical = '';

        if ($border === self::CONSOLE_TABLE_BORDER_ASCII) {
            $intersection = '+';
            $horizontal = '-';
            $vertical = '|';
        } else {
            if (is_string($border)) {
                $intersection = $horizontal = $vertical = $border;
            } else {
                if ($border == '') {
                    $intersection = $horizontal = $vertical = '';
                } else {
                    extract($border);
                }
            }
        }

        $this->_border = [
            'intersection' => $intersection,
            'horizontal'   => $horizontal,
            'vertical'     => $vertical,
        ];
    }

    /**
     * Set which borders shall be shown.
     *
     * @param array $visibility Visibility settings.
     *                          Allowed keys: left, right, top, bottom, inner
     *
     * @return void
     * @see    $_borderVisibility
     */
    function setBorderVisibility($visibility)
    {
        $this->_borderVisibility = array_merge(
            $this->_borderVisibility,
            array_intersect_key(
                $visibility,
                $this->_borderVisibility
            )
        );
    }

    /**
     * Sets the alignment for the columns.
     *
     * @param integer $col_id The column number.
     * @param integer $align  Alignment to set for this column. One of
     *                        self::CONSOLE_TABLE_ALIGN_LEFT
     *                        self::CONSOLE_TABLE_ALIGN_CENTER
     *                        self::CONSOLE_TABLE_ALIGN_RIGHT.
     *
     * @return void
     */
    function setAlign($col_id, $align = self::CONSOLE_TABLE_ALIGN_LEFT)
    {
        switch ($align) {
            case self::CONSOLE_TABLE_ALIGN_CENTER:
                $pad = STR_PAD_BOTH;
                break;
            case self::CONSOLE_TABLE_ALIGN_RIGHT:
                $pad = STR_PAD_LEFT;
                break;
            default:
                $pad = STR_PAD_RIGHT;
                break;
        }
        $this->_col_align[$col_id] = $pad;
    }

    /**
     * Specifies which columns are to have totals calculated for them and
     * added as a new row at the bottom.
     *
     * @param array $cols Array of column numbers (starting with 0).
     *
     * @return void
     */
    function calculateTotalsFor($cols)
    {
        $this->_calculateTotals = $cols;
    }

    /**
     * Sets the headers for the columns.
     *
     * @param array $headers The column headers.
     *
     * @return void
     */
    function setHeaders($headers)
    {
        $this->_headers = [array_values($headers)];
        $this->_updateRowsCols($headers);
    }

    /**
     * Adds a row to the table.
     *
     * @param array   $row    The row data to add.
     * @param boolean $append Whether to append or prepend the row.
     *
     * @return void
     */
    function addRow($row, $append = true)
    {
        if ($append) {
            $this->_data[] = array_values($row);
        } else {
            array_unshift($this->_data, array_values($row));
        }

        $this->_updateRowsCols($row);
    }

    /**
     * Inserts a row after a given row number in the table.
     *
     * If $row_id is not given it will prepend the row.
     *
     * @param array   $row    The data to insert.
     * @param integer $row_id Row number to insert before.
     *
     * @return void
     */
    function insertRow($row, $row_id = 0)
    {
        array_splice($this->_data, $row_id, 0, [$row]);

        $this->_updateRowsCols($row);
    }

    /**
     * Adds a column to the table.
     *
     * @param array   $col_data The data of the column.
     * @param integer $col_id   The column index to populate.
     * @param integer $row_id   If starting row is not zero, specify it here.
     *
     * @return void
     */
    function addCol($col_data, $col_id = 0, $row_id = 0)
    {
        foreach ($col_data as $col_cell) {
            $this->_data[$row_id++][$col_id] = $col_cell;
        }

        $this->_updateRowsCols();
        $this->_max_cols = max($this->_max_cols, $col_id + 1);
    }

    /**
     * Adds data to the table.
     *
     * @param array   $data   A two dimensional array with the table data.
     * @param integer $col_id Starting column number.
     * @param integer $row_id Starting row number.
     *
     * @return void
     */
    function addData($data, $col_id = 0, $row_id = 0)
    {
        foreach ($data as $row) {
            if ($row === self::CONSOLE_TABLE_HORIZONTAL_RULE) {
                $this->_data[$row_id] = self::CONSOLE_TABLE_HORIZONTAL_RULE;
                $row_id++;
                continue;
            }
            $starting_col = $col_id;
            foreach ($row as $cell) {
                $this->_data[$row_id][$starting_col++] = $cell;
            }
            $this->_updateRowsCols();
            $this->_max_cols = max($this->_max_cols, $starting_col);
            $row_id++;
        }
    }

    /**
     * Adds a horizontal seperator to the table.
     *
     * @return void
     */
    function addSeparator()
    {
        $this->_data[] = self::CONSOLE_TABLE_HORIZONTAL_RULE;
    }

    /**
     * Returns the generated table.
     *
     * @return string  The generated table.
     */
    function build()
    {
        $this->_calculateTotals();
        $this->_validateTable();

        return $this->_buildTable();
    }

    /**
     * Calculates totals for columns.
     *
     * @return void
     */
    function _calculateTotals()
    {
        if (empty($this->_calculateTotals)) {
            return;
        }

        $this->addSeparator();

        $totals = [];
        foreach ($this->_data as $row) {
            if (is_array($row)) {
                foreach ($this->_calculateTotals as $columnID) {
                    $totals[$columnID] += $row[$columnID];
                }
            }
        }

        $this->_data[] = $totals;
        $this->_updateRowsCols();
    }

    /**
     * Ensures that column and row counts are correct.
     *
     * @return void
     */
    function _validateTable()
    {
        if (!empty($this->_headers)) {
            $this->_calculateRowHeight(-1, $this->_headers[0]);
        }

        for ($i = 0; $i < $this->_max_rows; $i++) {
            for ($j = 0; $j < $this->_max_cols; $j++) {
                if (!isset($this->_data[$i][$j])
                    && (!isset($this->_data[$i])
                        || $this->_data[$i] !== self::CONSOLE_TABLE_HORIZONTAL_RULE)
                ) {
                    $this->_data[$i][$j] = '';
                }
            }
            $this->_calculateRowHeight($i, $this->_data[$i]);

            if ($this->_data[$i] !== self::CONSOLE_TABLE_HORIZONTAL_RULE) {
                ksort($this->_data[$i]);
            }
        }

        $this->_splitMultilineRows();

        // Update cell lengths.
        for ($i = 0; $i < count($this->_headers); $i++) {
            $this->_calculateCellLengths($this->_headers[$i]);
        }
        for ($i = 0; $i < $this->_max_rows; $i++) {
            $this->_calculateCellLengths($this->_data[$i]);
        }

        ksort($this->_data);
    }

    /**
     * Splits multiline rows into many smaller one-line rows.
     *
     * @return void
     */
    function _splitMultilineRows()
    {
        ksort($this->_data);
        $sections = [&$this->_headers, &$this->_data];
        $max_rows = [count($this->_headers), $this->_max_rows];
        $row_height_offset = [-1, 0];

        for ($s = 0; $s <= 1; $s++) {
            $inserted = 0;
            $new_data = $sections[$s];

            for ($i = 0; $i < $max_rows[$s]; $i++) {
                // Process only rows that have many lines.
                $height = $this->_row_heights[$i + $row_height_offset[$s]];
                if ($height > 1) {
                    // Split column data into one-liners.
                    $split = [];
                    for ($j = 0; $j < $this->_max_cols; $j++) {
                        $split[$j] = preg_split(
                            '/\r?\n|\r/',
                            $sections[$s][$i][$j]
                        );
                    }

                    $new_rows = [];
                    // Construct new 'virtual' rows - insert empty strings for
                    // columns that have less lines that the highest one.
                    for ($i2 = 0; $i2 < $height; $i2++) {
                        for ($j = 0; $j < $this->_max_cols; $j++) {
                            $new_rows[$i2][$j] = !isset($split[$j][$i2])
                                ? ''
                                : $split[$j][$i2];
                        }
                    }

                    // Replace current row with smaller rows.  $inserted is
                    // used to take account of bigger array because of already
                    // inserted rows.
                    array_splice($new_data, $i + $inserted, 1, $new_rows);
                    $inserted += count($new_rows) - 1;
                }
            }

            // Has the data been modified?
            if ($inserted > 0) {
                $sections[$s] = $new_data;
                $this->_updateRowsCols();
            }
        }
    }

    /**
     * Builds the table.
     *
     * @return string  The generated table string.
     */
    function _buildTable()
    {
        if (!count($this->_data)) {
            return '';
        }

        $vertical = $this->_border['vertical'];
        $separator = $this->_getSeparator();

        $return = [];
        for ($i = 0; $i < count($this->_data); $i++) {
            for ($j = 0; $j < count($this->_data[$i]); $j++) {
                if ($this->_data[$i] !== self::CONSOLE_TABLE_HORIZONTAL_RULE
                    && $this->_strlen($this->_data[$i][$j]) <
                       $this->_cell_lengths[$j]
                ) {
                    $this->_data[$i][$j] = $this->_strpad(
                        $this->_data[$i][$j],
                        $this->_cell_lengths[$j],
                        ' ',
                        $this->_col_align[$j]
                    );
                }
            }

            if ($this->_data[$i] !== self::CONSOLE_TABLE_HORIZONTAL_RULE) {
                $row_begin = $this->_borderVisibility['left']
                    ? $vertical . str_repeat(' ', $this->_padding)
                    : '';
                $row_end = $this->_borderVisibility['right']
                    ? str_repeat(' ', $this->_padding) . $vertical
                    : '';
                $implode_char = str_repeat(' ', $this->_padding) . $vertical
                                . str_repeat(' ', $this->_padding);
                $return[] = $row_begin
                            . implode($implode_char, $this->_data[$i]) . $row_end;
            } elseif (!empty($separator)) {
                $return[] = $separator;
            }
        }

        $return = implode(PHP_EOL, $return);
        if (!empty($separator)) {
            if ($this->_borderVisibility['inner']) {
                $return = $separator . PHP_EOL . $return;
            }
            if ($this->_borderVisibility['bottom']) {
                $return .= PHP_EOL . $separator;
            }
        }
        $return .= PHP_EOL;

        if (!empty($this->_headers)) {
            $return = $this->_getHeaderLine() . PHP_EOL . $return;
        }

        return $return;
    }

    /**
     * Creates a horizontal separator for header separation and table
     * start/end etc.
     *
     * @return string  The horizontal separator.
     */
    function _getSeparator()
    {
        if (!$this->_border) {
            return '';
        }

        $horizontal = $this->_border['horizontal'];
        $intersection = $this->_border['intersection'];

        $return = [];
        foreach ($this->_cell_lengths as $cl) {
            $return[] = str_repeat($horizontal, $cl);
        }

        $row_begin = $this->_borderVisibility['left']
            ? $intersection . str_repeat($horizontal, $this->_padding)
            : '';
        $row_end = $this->_borderVisibility['right']
            ? str_repeat($horizontal, $this->_padding) . $intersection
            : '';
        $implode_char = str_repeat($horizontal, $this->_padding) . $intersection
                        . str_repeat($horizontal, $this->_padding);

        return $row_begin . implode($implode_char, $return) . $row_end;
    }

    /**
     * Returns the header line for the table.
     *
     * @return string  The header line of the table.
     */
    function _getHeaderLine()
    {
        // Make sure column count is correct
        for ($j = 0; $j < count($this->_headers); $j++) {
            for ($i = 0; $i < $this->_max_cols; $i++) {
                if (!isset($this->_headers[$j][$i])) {
                    $this->_headers[$j][$i] = '';
                }
            }
        }

        for ($j = 0; $j < count($this->_headers); $j++) {
            for ($i = 0; $i < count($this->_headers[$j]); $i++) {
                if ($this->_strlen($this->_headers[$j][$i]) <
                    $this->_cell_lengths[$i]
                ) {
                    $this->_headers[$j][$i] =
                        $this->_strpad(
                            $this->_headers[$j][$i],
                            $this->_cell_lengths[$i],
                            ' ',
                            $this->_col_align[$i]
                        );
                }
            }
        }

        $vertical = $this->_border['vertical'];
        $row_begin = $this->_borderVisibility['left']
            ? $vertical . str_repeat(' ', $this->_padding)
            : '';
        $row_end = $this->_borderVisibility['right']
            ? str_repeat(' ', $this->_padding) . $vertical
            : '';
        $implode_char = str_repeat(' ', $this->_padding) . $vertical
                        . str_repeat(' ', $this->_padding);

        $separator = $this->_getSeparator();
        $return = [];

        if (!empty($separator) && $this->_borderVisibility['top']) {
            $return[] = $separator;
        }
        for ($j = 0; $j < count($this->_headers); $j++) {
            $return[] = $row_begin
                        . implode($implode_char, $this->_headers[$j]) . $row_end;
        }

        return implode(PHP_EOL, $return);
    }

    /**
     * Updates values for maximum columns and rows.
     *
     * @param array $rowdata Data array of a single row.
     *
     * @return void
     */
    function _updateRowsCols($rowdata = null)
    {
        $cntData = is_array($rowdata) ? count($rowdata) : 0;

        // Update maximum columns.
        $this->_max_cols = max($this->_max_cols, $cntData);

        // Update maximum rows.
        ksort($this->_data);
        $keys = array_keys($this->_data);
        $this->_max_rows = end($keys) + 1;

        switch ($this->_defaultAlign) {
            case self::CONSOLE_TABLE_ALIGN_CENTER:
                $pad = STR_PAD_BOTH;
                break;
            case self::CONSOLE_TABLE_ALIGN_RIGHT:
                $pad = STR_PAD_LEFT;
                break;
            default:
                $pad = STR_PAD_RIGHT;
                break;
        }

        // Set default column alignments
        for ($i = 0; $i < $this->_max_cols; $i++) {
            if (!isset($this->_col_align[$i])) {
                $this->_col_align[$i] = $pad;
            }
        }
    }

    /**
     * Calculates the maximum length for each column of a row.
     *
     * @param array $row The row data.
     *
     * @return void
     */
    function _calculateCellLengths($row)
    {
        for ($i = 0; $i < count($row); $i++) {
            if (!isset($this->_cell_lengths[$i])) {
                $this->_cell_lengths[$i] = 0;
            }
            $this->_cell_lengths[$i] = max(
                $this->_cell_lengths[$i],
                $this->_strlen($row[$i])
            );
        }
    }

    /**
     * Calculates the maximum height for all columns of a row.
     *
     * @param integer $row_number The row number.
     * @param array   $row        The row data.
     *
     * @return void
     */
    function _calculateRowHeight($row_number, $row)
    {
        if (!isset($this->_row_heights[$row_number])) {
            $this->_row_heights[$row_number] = 1;
        }

        // Do not process horizontal rule rows.
        if ($row === self::CONSOLE_TABLE_HORIZONTAL_RULE) {
            return;
        }

        for ($i = 0, $c = count($row); $i < $c; ++$i) {
            $lines = preg_split('/\r?\n|\r/', $row[$i]);
            $this->_row_heights[$row_number] = max(
                $this->_row_heights[$row_number],
                count($lines)
            );
        }
    }

    /**
     * Returns the character length of a string.
     *
     * @param string $str A multibyte or singlebyte string.
     *
     * @return integer  The string length.
     */
    function _strlen($str)
    {
        static $mbstring;

        // Cache expensive function_exists() calls.
        if (!isset($mbstring)) {
            $mbstring = function_exists('mb_strwidth');
        }

        if ($mbstring) {
            return mb_strwidth($str, $this->_charset);
        }

        return strlen($str);
    }

    /**
     * Returns part of a string.
     *
     * @param string  $string The string to be converted.
     * @param integer $start  The part's start position, zero based.
     * @param integer $length The part's length.
     *
     * @return string  The string's part.
     */
    function _substr($string, $start, $length = null)
    {
        static $mbstring;

        // Cache expensive function_exists() calls.
        if (!isset($mbstring)) {
            $mbstring = function_exists('mb_substr');
        }

        if (is_null($length)) {
            $length = $this->_strlen($string);
        }
        if ($mbstring) {
            $ret = @mb_substr($string, $start, $length, $this->_charset);
            if (!empty($ret)) {
                return $ret;
            }
        }
        return substr($string, $start, $length);
    }

    /**
     * Returns a string padded to a certain length with another string.
     *
     * This method behaves exactly like str_pad but is multibyte safe.
     *
     * @param string  $input  The string to be padded.
     * @param integer $length The length of the resulting string.
     * @param string  $pad    The string to pad the input string with. Must
     *                        be in the same charset like the input string.
     * @param integer $type   The padding type. One of STR_PAD_LEFT,
     *                        STR_PAD_RIGHT, or STR_PAD_BOTH.
     *
     * @return string  The padded string.
     */
    function _strpad($input, $length, $pad = ' ', $type = STR_PAD_RIGHT)
    {
        $mb_length = $this->_strlen($input);
        $sb_length = strlen($input);
        $pad_length = $this->_strlen($pad);

        /* Return if we already have the length. */
        if ($mb_length >= $length) {
            return $input;
        }

        /* Shortcut for single byte strings. */
        if ($mb_length == $sb_length && $pad_length == strlen($pad)) {
            return str_pad($input, $length, $pad, $type);
        }
        $output = '';
        switch ($type) {
            case STR_PAD_LEFT:
                $left = $length - $mb_length;
                $output = $this->_substr(
                        str_repeat($pad, ceil($left / $pad_length)),
                        0, $left
                    ) . $input;
                break;
            case STR_PAD_BOTH:
                $left = floor(($length - $mb_length) / 2);
                $right = ceil(($length - $mb_length) / 2);
                $output = $this->_substr(
                        str_repeat($pad, ceil($left / $pad_length)),
                        0, $left
                    ) .
                          $input .
                          $this->_substr(
                              str_repeat($pad, ceil($right / $pad_length)),
                              0, $right
                          );
                break;
            case STR_PAD_RIGHT:
                $right = $length - $mb_length;
                $output = $input .
                          $this->_substr(
                              str_repeat($pad, ceil($right / $pad_length)),
                              0, $right
                          );
                break;
        }

        return $output;
    }
}
