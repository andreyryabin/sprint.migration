<?php

namespace Sprint\Migration;

class Out
{

    protected static $colors = array(
        '/' => array("\x1b[0m", '</span>'),
        'is_unknown' => array("\x1b[0;34m", '<span style="color:#00a">'),
        'is_installed' => array("\x1b[0;32m", '<span style="color:#080">'),
        'is_new' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'blue' => array("\x1b[0;34m", '<span style="color:#00a">'),
        'green' => array("\x1b[0;32m", '<span style="color:#080">'),
        'red' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'yellow' => array("\x1b[1;33m", '<span style="color:#aa0">'),
        'b' => array("\x1b[1m", '<span style="font-weight:bold;color:#000">')
    );

    private static $needEol = false;
    
    public static function out($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }
        if (self::canOutAsHtml()) {
            self::outToHtml($msg);
        } else {
            self::outToConsole($msg );
        }
    }

    public static function outToConsoleOnly($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }
        if (!self::canOutAsHtml()) {
            self::outToConsole($msg );
        }
    }

    public static function outProgress($msg, $val, $total){
        $val = (int) $val;
        $total = (int) $total;

        if (self::canOutAsAdminMessage()) {

            if (self::canOutProgressBar()){
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                \CAdminMessage::ShowMessage(array(
                    "MESSAGE" => $msg,
                    "DETAILS" => "#PROGRESS_BAR#",
                    "HTML" => true,
                    "TYPE" => "PROGRESS",
                    "PROGRESS_TOTAL" => $total,
                    "PROGRESS_VALUE" => $val,
                ));
            } else {
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                \CAdminMessage::ShowMessage(array(
                    "MESSAGE" =>  $msg . ' ' . round($val / $total * 100) . '%',
                    'HTML' => true,
                    'TYPE' => 'OK'
                ));
            }


        } elseif (self::canOutAsHtml()) {
            $msg = self::prepareToHtml($msg);
            echo "$msg $val/$total <br/>";

        } else {
            self::$needEol = true;
            $msg = self::prepareToConsole($msg);
            fwrite(STDOUT, "\r$msg $val/$total");
        }

    }

    public static function outSuccess($msg, $var1 = null, $var2 = null){
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }
        if (self::canOutAsAdminMessage()) {
            $msg = self::prepareToHtml($msg);
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => $msg,
                'HTML' => true,
                'TYPE' => 'OK'
            ));
        } elseif (self::canOutAsHtml()) {
            self::outToHtml('[green]' . $msg . '[/]');

        } else {
            self::outToConsole($msg );
        }
    }

    public static function outError($msg, $var1 = null, $var2 = null){
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }
        if (self::canOutAsAdminMessage()) {
            $msg = self::prepareToHtml($msg);
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => $msg,
                'HTML' => true,
                'TYPE' => 'ERROR'
            ));
        } elseif (self::canOutAsHtml()) {
            self::outToHtml('[red]' . $msg . '[/]');
        } else {
            self::outToConsole($msg);
        }
    }


    protected static $tableRows = array();
    protected static $tableMaxCol = array();
    protected static $tableHeaderExists = 0;

    public static function addTableRow($row){
        self::calcMaxColumn($row);
        self::$tableRows[] = $row;
    }

    protected static function calcMaxColumn($row){
        foreach ($row as $colNum => $col){
            $len = self::strLen($col);
            if (!isset(self::$tableMaxCol[$colNum])){
                self::$tableMaxCol[$colNum] = 0;
            }
            if ($len >= self::$tableMaxCol[$colNum]){
                self::$tableMaxCol[$colNum] = $len;
            }
        }
    }

    public static function initTable($headerRow = array()){
        self::$tableRows = array();
        self::$tableMaxCol = array();
        self::$tableHeaderExists = 0;

        if (!empty($headerRow)){
            self::calcMaxColumn($headerRow);
            array_unshift(self::$tableRows, $headerRow);
            self::$tableHeaderExists = 1;
        }
    }

    protected static function outTableSep(){
        $res = '';
        $colCnt = count(self::$tableMaxCol);
        foreach (self::$tableMaxCol as $colNum => $colLen){
            $border = ($colNum < $colCnt - 1 ) ? '+' : '';
            $res .=  ' ' . self::strPad('', self::$tableMaxCol[$colNum], '-') . ' ' .$border;
        }

        Out::out('+' . $res . '+');
    }

    protected static function outTableContent($row){
        $colCnt = count(self::$tableMaxCol);
        $res = '';
        foreach ($row as $colNum => $col){
            $border = ($colNum < $colCnt - 1 ) ? '|' : '';
            $res .=  ' ' . self::strPad($col, self::$tableMaxCol[$colNum], ' ') . ' ' . $border;
        }
        Out::out('|' . $res . '|');
    }

    public static function outTable(){
        $rowscnt = count(self::$tableRows);
        foreach (self::$tableRows as $rowNum => $row){
            if ($rowNum == 0){
                self::outTableSep();
            }

            self::outTableContent($row);

            if ($rowNum == 0 && self::$tableHeaderExists){
                self::outTableSep();
            }

            if ($rowNum == $rowscnt - 1 ){
                self::outTableSep();
            }
        }
    }

    function strLen($str){
        if (Module::isWin1251()){
            return strlen($str);
        } else {
            return mb_strlen($str, 'UTF-8');
        }

    }
    function strPad($input, $pad_length, $pad_string) {
        if (Module::isWin1251()){
            return str_pad($input, $pad_length, $pad_string, STR_PAD_RIGHT);
        } else {
            return str_pad($input, strlen($input)-mb_strlen($input,'UTF-8')+$pad_length, $pad_string, STR_PAD_RIGHT);
        }
    }
    
    protected static function outToHtml($msg){
        $msg = self::prepareToHtml($msg);
        echo "$msg <br/>";
    }

    protected static function outToConsole($msg){
        $msg = self::prepareToConsole($msg);
        if (self::$needEol){
            self::$needEol = false;
            fwrite(STDOUT, PHP_EOL . $msg . PHP_EOL);
        } else {
            fwrite(STDOUT, $msg . PHP_EOL);
        }
    }

    protected static function prepareToConsole($msg){
        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[0], $msg);
        }

        if (Module::isWin1251()){
            $msg = iconv('windows-1251', 'utf-8//IGNORE', $msg);
        }

        return $msg;
    }

    protected static function prepareToHtml($msg){
        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[1], $msg);
        }
        return $msg;
    }
    
    protected static function canOutAsAdminMessage(){
        return (self::canOutAsHtml() && class_exists('\CAdminMessage')) ? 1 : 0;
    }

    protected static function canOutProgressBar(){
        return method_exists('\CAdminMessage', '_getProgressHtml') ? 1 :0;
    }

    protected static function canOutAsHtml(){
        return ( php_sapi_name() == 'cli' ) ? 0 : 1;
    }
}
