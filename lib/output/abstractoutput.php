<?php

namespace Sprint\Migration\Output;

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

        $makeRelative = function (string $path, int $depth = 0) {
            $chunks = explode(DIRECTORY_SEPARATOR, $path);
            $chunks = array_slice($chunks, -($depth + 1));

            return '.../' . implode('/', $chunks);
        };

        $this->outWarning(
            "[%s] %s (%s) in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $makeRelative($exception->getFile(), 2),
            $exception->getLine()
        );

        foreach ($exception->getTrace() as $err) {
            $this->out('%s:%d', $makeRelative($err['file'], 2), $err['line']);
        }
    }


}
