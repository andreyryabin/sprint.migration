<?php

namespace Sprint\Migration\Output;

use Sprint\Migration\VersionManager;

abstract class AbstractOutput implements OutputInterface
{
    public function outInfo(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->out('[blue]' . $msg . '[/]');
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->out('[label:green]' . $msg . '[/]');
    }

    public function outNotice(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->out('[green]' . $msg . '[/]');
    }

    public function outError(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->out('[label:red]' . $msg . '[/]');
    }

    public function outWarning(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->out('[red]' . $msg . '[/]');
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        $diff1 = $this->getArrayFlat(
            $this->getArrayDiff($arr2, $arr1)
        );

        $diff2 = $this->getArrayFlat(
            $this->getArrayDiff($arr1, $arr2)
        );

        $diff = array_merge($diff1, $diff2);

        foreach ($diff as $k => $v) {
            if (isset($diff1[$k]) && isset($diff2[$k])) {
                $this->out($k . ': [red]' . htmlspecialchars($diff2[$k]) . '[/] -> [green]' . htmlspecialchars($diff1[$k]) . '[/]');
            } elseif (isset($diff1[$k])) {
                $this->out($k . ': [green]' . htmlspecialchars($diff1[$k]) . '[/]');
            } else {
                $this->out($k . ': [red]' . htmlspecialchars($diff2[$k]) . '[/]');
            }
        }
    }

    private function getArrayFlat(array $arr): array
    {
        $out = [];
        $this->makeArrayFlatRecursive($out, '', $arr);
        return $out;
    }

    private function makeArrayFlatRecursive(array &$out, $key, array $in): void
    {
        foreach ($in as $k => $v) {
            if (is_array($v)) {
                $this->makeArrayFlatRecursive($out, $key . $k . '.', $v);
            } else {
                $out[$key . $k] = $v;
            }
        }
    }

    private function getArrayDiff(array $array1, array $array2): array
    {
        return $this->makeArrayDiffRecursive($array1, $array2);
    }

    private function makeArrayDiffRecursive(array $array1, array $array2): array
    {
        $diff = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $diff[$key] = $value;
                } else {
                    $newDiff = $this->makeArrayDiffRecursive($value, $array2[$key]);
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

    public function outMessages(array $messages = []): void
    {
        foreach ($messages as $val) {
            if ($val['success']) {
                $this->outSuccess($val['message']);
            } else {
                $this->outError($val['message']);
            }
        }
    }

    public function outException(?\Throwable $exception = null): void
    {
        if (!$exception) {
            return;
        }

        $trace = $exception->getTrace();
        $offset = $this->startMigrationOffset($trace);

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

        $this->outWarning(
            "[%s] %s (%s) in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $file,
            $line

        );
        $this->outExceptionTrace($trace);
    }

    private function startMigrationOffset(array $trace): int
    {
        foreach ($trace as $index => $item) {
            if ($item['class'] == VersionManager::class && $item['function'] == 'startMigration') {
                return (int)$index;
            }
        }
        return -1;
    }

    private function outExceptionTrace(array $trace): void
    {
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
                $this->out('[b]#' . $index . '[/] ' . $name . '();');
                continue;
            }

            $this->out('[b]#' . $index . '[/] ' . $name . '(');
            foreach ($err['args'] as $argi => $argval) {
                $del = $argi < $cntArgs - 1 ? ', ' : '';
                $this->out('[tab]' . htmlspecialchars(var_export($argval, 1)) . $del . '[/]');
            }
            $this->out(');');
        }
    }
}
