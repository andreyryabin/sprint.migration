<?php

namespace Sprint\Migration;

class Out
{

    protected static $colors = array(
        '/' => array("\x1b[0m", '</span>'),
        'is_unknown' => array("\x1b[0;34m", '<span style="color:#00a">'),
        'is_installed' => array("\x1b[0;32m", '<span style="color:#080">'),
        'is_new' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'unknown' => array("\x1b[0;34m", '<span style="color:#00a">'),
        'installed' => array("\x1b[0;32m", '<span style="color:#080">'),
        'new' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'blue' => array("\x1b[0;34m", '<span style="color:#00a">'),
        'green' => array("\x1b[0;32m", '<span style="color:#080">'),
        'up' => array("\x1b[0;32m", '<span style="color:#080">'),
        'red' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'down' => array("\x1b[0;31m", '<span style="color:#a00">'),
        'yellow' => array("\x1b[1;33m", '<span style="color:#aa0">'),
        'b' => array("\x1b[1m", '<span style="font-weight:bold;color:#000">'),
    );

    protected static $needEol = false;

    public static function out($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }
        if (self::canOutAsHtml()) {
            self::outToHtml($msg);
        } else {
            self::outToConsole($msg);
        }
    }

    public static function outProgress($msg, $val, $total) {
        $val = (int)$val;
        $total = (int)$total;

        self::$needEol = true;

        if (self::canOutProgressBar()) {
            $mess = array(
                "MESSAGE" => $msg,
                "DETAILS" => "#PROGRESS_BAR#",
                "HTML" => true,
                "TYPE" => "PROGRESS",
                "PROGRESS_TOTAL" => $total,
                "PROGRESS_VALUE" => $val,
            );

            $m = new \CAdminMessage($mess);
            echo '<div class="sp-progress">' . $m->Show() . '</div>';

        } elseif (self::canOutAsHtml()) {
            $msg = self::prepareToHtml($msg);
            echo '<div class="sp-progress">' . "$msg $val/$total" . '</div>';

        } else {
            $msg = self::prepareToConsole($msg);
            fwrite(STDOUT, "\r$msg $val/$total");
        }

    }

    public static function outNotice($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = '[green]' . $msg . '[/]';
        if (self::canOutAsHtml()) {
            self::outToHtml($msg);
        } else {
            self::outToConsole($msg);
        }
    }

    public static function outWarning($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = '[red]' . $msg . '[/]';
        if (self::canOutAsHtml()) {
            self::outToHtml($msg);
        } else {
            self::outToConsole($msg);
        }
    }

    public static function outInfo($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = '[blue]' . $msg . '[/]';
        if (self::canOutAsHtml()) {
            self::outToHtml($msg);
        } else {
            self::outToConsole($msg);
        }
    }

    public static function outError($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => self::prepareToHtml($msg),
                'HTML' => true,
                'TYPE' => 'ERROR'
            ));

        } else {
            self::outWarning($msg);
        }
    }

    public static function outSuccess($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => self::prepareToHtml($msg),
                'HTML' => true,
                'TYPE' => 'OK'
            ));
        } else {
            self::outNotice($msg);
        }
    }

    public static function outIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'out'), $args);
        }

    }

    public static function outInfoIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'outInfo'), $args);
        }
    }

    public static function outWarningIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'outWarning'), $args);
        }
    }

    public static function outErrorIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'outError'), $args);
        }
    }

    public static function outNoticeIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'outNotice'), $args);
        }
    }

    public static function outSuccessIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array(__CLASS__, 'outSuccess'), $args);
        }
    }

    public static function prepareToConsole($msg) {
        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[0], $msg);
        }

        $msg = Locale::convertToUtf8IfNeed($msg);
        return $msg;
    }

    public static function prepareToHtml($msg) {
        $msg = nl2br($msg);

        $msg = str_replace('[t]', '&rarr;', $msg);

        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[1], $msg);
        }

        $msg = Locale::convertToWin1251IfNeed($msg);
        return $msg;
    }

    public static function input($field) {
        if (self::canOutAsHtml()) {
            return false;
        }

        if (!empty($field['items'])) {
            self::inputStructure($field);
        } elseif (!empty($field['select'])) {
            self::inputSelect($field);
        } else {
            self::inputText($field);
        }

        $val = fgets(STDIN);
        $val = trim($val);

        if ($field['multiple']) {
            $val = explode(' ', $val);
            $val = array_filter($val, function ($a) {
                return !empty($a);
            });
        }

        return $val;
    }

    public static function outDiffIf($cond, $arr1, $arr2){
        if ($cond){
            self::outDiff($arr1, $arr2);
        }
    }

    public static function outDiff($arr1, $arr2) {
        $diff1 = self::getArrayFlat(
            self::getArrayDiff($arr2, $arr1)
        );

        $diff2 = self::getArrayFlat(
            self::getArrayDiff($arr1, $arr2)
        );

        $diff = array_merge($diff1, $diff2);

        foreach ($diff as $k => $v) {
            if (isset($diff1[$k]) && isset($diff2[$k])) {
                self::out($k . ': [red]' . $diff2[$k] . '[/] -> [green]' . $diff1[$k] . '[/]');
            } elseif (isset($diff1[$k])) {
                self::out($k . ': [green]' . $diff1[$k] . '[/]');
            } else {
                self::out($k . ': [red]' . $diff2[$k] . '[/]');
            }
        }
    }

    protected static function outToHtml($msg) {
        $msg = self::prepareToHtml($msg);
        echo '<div class="sp-out">' . $msg . '</div>';
    }

    protected static function outToConsole($msg, $rightEol = PHP_EOL) {
        $msg = self::prepareToConsole($msg);
        if (self::$needEol) {
            self::$needEol = false;
            fwrite(STDOUT, PHP_EOL . $msg . $rightEol);
        } else {
            fwrite(STDOUT, $msg . $rightEol);
        }
    }

    protected static function canOutAsAdminMessage() {
        return (self::canOutAsHtml() && class_exists('\CAdminMessage')) ? 1 : 0;
    }

    protected static function canOutProgressBar() {
        return method_exists('\CAdminMessage', '_getProgressHtml') ? 1 : 0;
    }

    protected static function canOutAsHtml() {
        return (php_sapi_name() == 'cli') ? 0 : 1;
    }

    protected static function inputText($field) {
        self::outToConsole($field['title'] . ':', '');
    }

    protected static function inputSelect($field) {
        foreach ($field['select'] as $item) {
            self::outToConsole(' > ' . $item['value'] . ' (' . $item['title'] . ')');
        }
        self::outToConsole($field['title'] . ':', '');
    }

    protected static function inputStructure($field) {
        foreach ($field['items'] as $group) {
            self::outToConsole('---' . $group['title']);
            foreach ($group['items'] as $item) {
                self::outToConsole(' > ' . $item['value'] . ' (' . $item['title'] . ')');
            }
        }
        self::outToConsole($field['title'] . ':', '');
    }

    protected static function getArrayFlat($arr) {
        $out = array();
        self::makeArrayFlatRecursive($out, '', $arr);
        return $out;
    }

    protected static function getArrayDiff($array1, $array2) {
        return self::makeArrayDiffRecursive($array1, $array2);
    }

    protected static function makeArrayFlatRecursive(array &$out, $key, array $in) {
        foreach ($in as $k => $v) {
            if (is_array($v)) {
                self::makeArrayFlatRecursive($out, $key . $k . '.', $v);
            } else {
                $out[$key . $k] = $v;
            }
        }
    }

    protected static function makeArrayDiffRecursive(array $array1, array $array2) {
        $diff = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $diff[$key] = $value;
                } else {
                    $newDiff = self::makeArrayDiffRecursive($value, $array2[$key]);
                    if (!empty($newDiff)) {
                        $diff[$key] = $newDiff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }

}
