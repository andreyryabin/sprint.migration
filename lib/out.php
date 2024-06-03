<?php

namespace Sprint\Migration;

use CAdminMessage;
use Throwable;

class Out
{
    protected static $colors  = [
        '/'            => ["\x1b[0m", '</span>'],
        'is_unknown'   => ["\x1b[0;34m", '<span style="color:#00a">'],
        'is_installed' => ["\x1b[0;32m", '<span style="color:#080">'],
        'is_new'       => ["\x1b[0;31m", '<span style="color:#a00">'],
        'unknown'      => ["\x1b[0;34m", '<span style="color:#00a">'],
        'installed'    => ["\x1b[0;32m", '<span style="color:#080">'],
        'new'          => ["\x1b[0;31m", '<span style="color:#a00">'],
        'blue'         => ["\x1b[0;34m", '<span style="color:#00a">'],
        'green'        => ["\x1b[0;32m", '<span style="color:#080">'],
        'up'           => ["\x1b[0;32m", '<span style="color:#080">'],
        'red'          => ["\x1b[0;31m", '<span style="color:#a00">'],
        'down'         => ["\x1b[0;31m", '<span style="color:#a00">'],
        'yellow'       => ["\x1b[1;33m", '<span style="color:#aa0">'],
        'tab'          => ["", '<span style="display: inline-block; margin-left: 10px">'],
        'b'            => ["\x1b[1m", '<span style="font-weight:bold;color:#000">'],
    ];
    protected static $needEol = false;

    public static function outProgress($msg, $val, $total)
    {
        $val = (int)$val;
        $total = (int)$total;

        self::$needEol = true;

        if (self::canOutProgressBar()) {
            $mess = [
                "MESSAGE"        => $msg,
                "DETAILS"        => "#PROGRESS_BAR#",
                "HTML"           => true,
                "TYPE"           => "PROGRESS",
                "PROGRESS_TOTAL" => $total,
                "PROGRESS_VALUE" => $val,
            ];

            echo '<div class="sp-progress">' . (new CAdminMessage($mess))->Show() . '</div>';
        } elseif (self::canOutAsHtml()) {
            $msg = self::prepareToHtml($msg, ['br' => true]);
            echo '<div class="sp-progress">' . "$msg $val/$total" . '</div>';
        } else {
            $msg = self::prepareToConsole($msg);
            fwrite(STDOUT, "\r$msg $val/$total");
        }
    }

    protected static function canOutProgressBar()
    {
        return method_exists('\CAdminMessage', '_getProgressHtml') ? 1 : 0;
    }

    protected static function canOutAsHtml()
    {
        return (php_sapi_name() == 'cli') ? 0 : 1;
    }

    protected static function prepareToHtml($msg, $options = [])
    {
        if ($options['br']) {
            $msg = nl2br($msg);
        }

        $msg = str_replace('[t]', '&rarr;', $msg);

        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[1], $msg);
        }

        if (!empty($options['tracker_task_url'])) {
            $msg = self::makeTaskUrl($msg, $options['tracker_task_url']);
        }

        if (!empty($options['make_links'])) {
            $msg = self::makeLinksHtml($msg);
        }

        return Locale::convertToWin1251IfNeed($msg);
    }

    protected static function makeTaskUrl($msg, $taskUrl = '')
    {
        if (false !== strpos($taskUrl, '$1')) {
            $msg = preg_replace('/\#([a-z0-9_\-]*)/i', $taskUrl, $msg);
        }

        return $msg;
    }

    protected static function makeLinksHtml($msg)
    {
        $reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        if (preg_match($reg_exUrl, $msg, $url)) {
            $msg = preg_replace($reg_exUrl, '<a target="_blank" href="' . $url[0] . '">' . $url[0] . '</a>', $msg);
        }

        return $msg;
    }

    public static function prepareToConsole($msg, $options = [])
    {
        if (!empty($options['tracker_task_url'])) {
            $msg = self::makeTaskUrl($msg, $options['tracker_task_url']);
        }

        if ($options['max_len']) {
            $msg = self::truncateText($msg, $options['max_len']) . '[/]';
        }

        foreach (self::$colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val[0], $msg);
        }

        return Locale::convertToUtf8IfNeed($msg);
    }

    public static function outInfo($msg, ...$vars)
    {
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

    public static function outToHtml($msg, $options = ['br' => false])
    {
        $class = $options['class'] ?? 'sp-out';

        $msg = $msg ? self::prepareToHtml($msg, $options) : '';

        echo $msg ? '<div class="' . $class . '">' . $msg . '</div>' : '';
    }

    public static function outToConsole($msg, $options = [], $rightEol = PHP_EOL)
    {
        $msg = self::prepareToConsole($msg, $options);
        if (self::$needEol) {
            self::$needEol = false;
            fwrite(STDOUT, PHP_EOL . $msg . $rightEol);
        } else {
            fwrite(STDOUT, $msg . $rightEol);
        }
    }

    public static function outIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'out'], $args);
        }
    }

    public static function outInfoIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'outInfo'], $args);
        }
    }

    public static function outWarningIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'outWarning'], $args);
        }
    }

    public static function outErrorIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'outError'], $args);
        }
    }

    public static function outNoticeIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'outNotice'], $args);
        }
    }

    public static function outSuccessIf($cond, $msg, ...$vars)
    {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array([__CLASS__, 'outSuccess'], $args);
        }
    }

    public static function input($field)
    {
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
            $val = array_filter($val);
        }

        return $val;
    }

    protected static function inputStructure($field)
    {
        foreach ($field['items'] as $group) {
            self::outToConsole('---' . $group['title']);
            foreach ($group['items'] as $item) {
                self::outToConsole(' > ' . $item['value'] . ' (' . $item['title'] . ')');
            }
        }
        self::outToConsole($field['title'] . ':', [], '');
    }

    protected static function inputSelect($field)
    {
        foreach ($field['select'] as $item) {
            self::outToConsole(' > ' . $item['value'] . ' (' . $item['title'] . ')');
        }
        self::outToConsole($field['title'] . ':', [], '');
    }

    protected static function inputText($field)
    {
        self::outToConsole($field['title'] . ':', [], '');
    }

    public static function outDiffIf($cond, $arr1, $arr2)
    {
        if ($cond) {
            self::outDiff($arr1, $arr2);
        }
    }

    public static function outDiff($arr1, $arr2)
    {
        $diff1 = self::getArrayFlat(
            self::getArrayDiff($arr2, $arr1)
        );

        $diff2 = self::getArrayFlat(
            self::getArrayDiff($arr1, $arr2)
        );

        $diff = array_merge($diff1, $diff2);

        foreach ($diff as $k => $v) {
            if (isset($diff1[$k]) && isset($diff2[$k])) {
                self::out($k . ': [red]' . htmlspecialchars($diff2[$k]) . '[/] -> [green]' . htmlspecialchars($diff1[$k]) . '[/]');
            } elseif (isset($diff1[$k])) {
                self::out($k . ': [green]' . htmlspecialchars($diff1[$k]) . '[/]');
            } else {
                self::out($k . ': [red]' . htmlspecialchars($diff2[$k]) . '[/]');
            }
        }
    }

    protected static function truncateText($strText, $intLen)
    {
        if (mb_strlen($strText) > $intLen) {
            return rtrim(mb_substr($strText, 0, $intLen), ".") . "...";
        } else {
            return $strText;
        }
    }

    protected static function getArrayFlat($arr)
    {
        $out = [];
        self::makeArrayFlatRecursive($out, '', $arr);
        return $out;
    }

    protected static function makeArrayFlatRecursive(array &$out, $key, array $in)
    {
        foreach ($in as $k => $v) {
            if (is_array($v)) {
                self::makeArrayFlatRecursive($out, $key . $k . '.', $v);
            } else {
                $out[$key . $k] = $v;
            }
        }
    }

    protected static function getArrayDiff($array1, $array2)
    {
        return self::makeArrayDiffRecursive($array1, $array2);
    }

    protected static function makeArrayDiffRecursive(array $array1, array $array2)
    {
        $diff = [];
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

    public static function out($msg, ...$vars)
    {
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

    public static function outMessages($messages = [])
    {
        foreach ($messages as $val) {
            if ($val['success']) {
                self::outSuccess($val['message']);
            } else {
                self::outError($val['message']);
            }
        }
    }

    public static function outSuccess($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            echo (new CAdminMessage(
                [
                    "MESSAGE" => self::prepareToHtml($msg, ['br' => true]),
                    'HTML'    => true,
                    'TYPE'    => 'OK',
                ]
            ))->Show();
        } else {
            self::outNotice($msg);
        }
    }

    protected static function canOutAsAdminMessage()
    {
        return (self::canOutAsHtml() && class_exists('\CAdminMessage')) ? 1 : 0;
    }

    public static function outNotice($msg, ...$vars)
    {
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

    public static function outError($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        if (self::canOutAsAdminMessage()) {
            echo (new CAdminMessage(
                [
                    "MESSAGE" => self::prepareToHtml($msg, ['br' => true]),
                    'HTML'    => true,
                    'TYPE'    => 'ERROR',
                ]
            ))->Show();
        } else {
            self::outWarning($msg);
        }
    }

    public static function outWarning($msg, ...$vars)
    {
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

    public static function outException(?Throwable $exception)
    {
        if (!$exception) {
            return;
        }

        $trace = $exception->getTrace();
        $offset = self::startMigrationOffset($trace);

        $file = $exception->getFile();
        $line = $exception->getLine();

        if ($offset >= 0) {
            $trace = array_slice($trace, 0, $offset);
            if (count($trace) > 1) {
                $first = $trace[0];
                $file = $first['file'];
                $line = $first['line'];
            }
        }

        self::outWarning(
            "[%s] %s (%s) in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $file,
            $line

        );
        self::outExceptionTrace($trace);
    }

    protected static function startMigrationOffset(array $trace)
    {
        foreach ($trace as $index => $item) {
            if ($item['class'] == VersionManager::class && $item['function'] == 'startMigration') {
                return $index;
            }
        }
        return -1;
    }

    protected static function outExceptionTrace(array $trace)
    {
        $isBrowser = self::canOutAsHtml();

        foreach ($trace as $index => $err) {
            $name = '';
            if ($err['class'] && $err['function']) {
                $name = '[b]' . $err['class'] . '[/]::' . $err['function'];
            } elseif ($err['function']) {
                $name = '[b]' . $err['function'] . '[/]';
            }

            $err['args'] = (array)($err['args'] ?? []);
            $cntArgs = count($err['args']);

            if ($cntArgs == 0) {
                self::out('[b]#' . $index . '[/] ' . $name . '();');
                continue;
            }

            self::out('[b]#' . $index . '[/] ' . $name . '(');
            foreach ($err['args'] as $argi => $argval) {
                $del = $argi < $cntArgs - 1 ? ', ' : '';

                $argval = var_export($argval, 1);
                $argval = $isBrowser ? htmlspecialchars($argval) : $argval;

                self::out('[tab]' . $argval . $del . '[/]');
            }
            self::out(');');
        }
    }
}
