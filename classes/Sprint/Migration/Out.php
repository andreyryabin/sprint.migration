<?php

namespace Sprint\Migration;

class Out
{

    protected static $colorsConsole = array(
        'is_404' => '0;34',
        'is_success' => '0;32',
        'is_new' => '0;31',
        'blue' => '0;34',
        'green' => '0;32',
        'red' => '0;31',
        'yellow' => '1;33',
    );

    protected static $colorsDefault = array(
        'is_404' => '#00a',
        'is_success' => '#080',
        'is_new' => '#a00',
        'blue' => '#00a',
        'green' => '#080',
        'red' => '#a00',
        'yellow' => '#aa0',
    );

    private static $needEol = false;
    
    public static function out($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        self::outDefault($msg);
    }

    public static function outSuccess($msg, $var1 = null, $var2 = null){
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            $msg = self::prepareToHtml($msg);
            \CAdminMessage::ShowMessage(array("MESSAGE" => $msg, 'HTML' => true, 'TYPE' => 'OK'));

        } else {
            self::outDefault($msg);
        }
    }

    public static function outError($msg, $var1 = null, $var2 = null){
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            $msg = self::prepareToHtml($msg);
            \CAdminMessage::ShowMessage(array("MESSAGE" => $msg, 'HTML' => true, 'TYPE' => 'ERROR'));

        } else {
            self::outDefault($msg);
        }
    }

    protected static function outDefault($msg){
        if (self::canOutAsHtml()){
            $msg = self::prepareToHtml($msg);
            echo "$msg <br/>";

        } else {
            $msg = self::prepareToConsole($msg);
            if (self::$needEol){
                self::$needEol = false;
                fwrite(STDOUT, PHP_EOL . $msg . PHP_EOL);
            } else {
                fwrite(STDOUT, $msg . PHP_EOL);
            }
        }
    }

    public static function outProgress($msg, $val, $total){
        $val = (int) $val;
        $total = (int) $total;

        if (self::canOutAsAdminMessage()) {
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => $msg,
                "DETAILS" => "#PROGRESS_BAR#",
                "HTML" => true,
                "TYPE" => "PROGRESS",
                "PROGRESS_TOTAL" => $total,
                "PROGRESS_VALUE" => $val,
            ));
        } elseif (self::canOutAsHtml()) {
            $msg = self::prepareToHtml($msg);
            echo "$msg $val/$total <br/>";

        } else {
            self::$needEol = true;
            $msg = self::prepareToConsole($msg);
            fwrite(STDOUT, "\r$msg $val/$total");
        }

    }


    protected static function prepareToConsole($msg){
        foreach (self::$colorsConsole as $key => $val) {
            $msg = str_replace('[' . $key . ']', "\033[" . $val . "m", $msg);
        }
        return str_replace('[/]', "\033[0m", $msg);
    }

    protected static function prepareToHtml($msg){
        foreach (self::$colorsDefault as $key => $val) {
            $msg = str_replace('[' . $key . ']', "<span style=\"color:" . $val . "\">", $msg);
        }
        return str_replace('[/]', "</span>", $msg);
    }
    
    protected function canOutAsAdminMessage(){
        return (!empty($_SERVER['HTTP_HOST']) && class_exists('\CAdminMessage')) ? 1 : 0;
    }

    protected function canOutAsHtml(){
        return (!empty($_SERVER['HTTP_HOST'])) ? 1 : 0;
    }    
}



