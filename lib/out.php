<?php

namespace Sprint\Migration;

use Throwable;

class Out
{
    protected static $colors = [
        '/' => ["\x1b[0m", '</span>'],
        'tab' => ["\x1b[0m", '<span class="sp-indent-20">'],
        'unknown' => ["\x1b[0;34m", '<span class="sp-blue"">'],
        'installed' => ["\x1b[0;32m", '<span class="sp-green">'],
        'new' => ["\x1b[0;31m", '<span class="sp-red">'],
        'blue' => ["\x1b[0;34m", '<span class="sp-blue">'],
        'pink' => ["\x1b[0;35m", '<span class="sp-pink">'],
        'green' => ["\x1b[0;32m", '<span class="sp-green">'],
        'up' => ["\x1b[0;32m", '<span class="sp-green">'],
        'red' => ["\x1b[0;31m", '<span class="sp-red">'],
        'down' => ["\x1b[0;31m", '<span class="sp-red">'],
        'yellow' => ["\x1b[0;93m", '<span class="sp-yellow">'],
        'label' => ["\x1b[47;30m", '<span class="sp-label">'],
        'label:blue' => ["\x1b[104;30m", '<span class="sp-label sp-label-blue">'],
        'label:pink' => ["\x1b[105;30m", '<span class="sp-label sp-label-pink">'],
        'label:red' => ["\x1b[41;37m", '<span class="sp-label sp-label-red">'],
        'label:green' => ["\x1b[102;30m", '<span class="sp-label sp-label-green">'],
        'label:yellow' => ["\x1b[103;30m", '<span class="sp-label sp-label-yellow">'],
        'b' => ["\x1b[1m", '<span class="sp-bold">'],
    ];
    protected static $needEol = false;

    public static function outProgress(string $msg, int $val, int $total): void
    {
        self::$needEol = true;

        $msg = '[label]' . $msg . ' ' . $val . ' / ' . $total . '[/]';

        if (self::canOutAsHtml()) {
            self::outToHtml($msg, ['class' => 'sp-out sp-progress']);
        } else {
            $msg = self::prepareToConsole($msg);
            fwrite(STDOUT, "\r$msg");
        }
    }

    protected static function canOutAsHtml(): bool
    {
        return (php_sapi_name() != 'cli');
    }

    protected static function prepareToHtml($msg, $options = [])
    {
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

        return $msg;
    }

    protected static function makeTaskUrl($msg, $taskUrl = '')
    {
        if (str_contains($taskUrl, '$1')) {
            $msg = preg_replace('/\#([a-z0-9_\-]*)/i', $taskUrl, $msg);
        }

        return $msg;
    }

    protected static function makeLinksHtml($msg)
    {
        $regex = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        if (preg_match_all($regex, $msg, $urls)) {
            foreach (array_unique($urls[0]) as $url) {
                $msg = str_replace($url, '<a target="_blank" href="' . $url . '">' . $url . '</a>', $msg);
            }
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

        return $msg;
    }

    public static function outInfo($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        self::out('[blue]' . $msg . '[/]');
    }

    public static function outToHtml($msg, $options = [])
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
        } elseif ($msg) {
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

    public static function outArray($arr)
    {
        $arr = self::getArrayFlat($arr);
        foreach ($arr as $k => $v) {
            self::out($k . ':' . $v);
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

    public static function getArrayFlat($arr)
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

        self::out('[label:green]' . $msg . '[/]');
    }

    public static function outNotice($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        self::out('[green]' . $msg . '[/]');
    }

    public static function outError($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        self::out('[label:red]' . $msg . '[/]');
    }

    public static function outWarning($msg, ...$vars)
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        self::out('[red]' . $msg . '[/]');
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
