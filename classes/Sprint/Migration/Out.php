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
        return (!empty($_SERVER['HTTP_HOST']) && class_exists('\CAdminMessage')) ? 1 : 0;
    }

    protected static function canOutProgressBar(){
        return method_exists('\CAdminMessage', '_getProgressHtml') ? 1 :0;
    }

    protected static function canOutAsHtml(){
        return (!empty($_SERVER['HTTP_HOST'])) ? 1 : 0;
    }    
}
