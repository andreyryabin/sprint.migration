<?php

namespace Sprint\Migration\Output;

abstract class AbstractOutput implements OutputInterface
{

    abstract protected function makeMessage(string $msg, string $type): void;

    abstract protected function makeProgress(string $msg, int $val, int $total): void;


    public function out(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, '');
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->makeProgress($msg, $val, $total);
    }

    public function outInfo(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, 'info');
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, 'success');
    }

    public function outNotice(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, 'notice');
    }

    public function outError(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, 'error');
    }

    public function outWarning(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->makeMessage($msg, 'warning');
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        $diff1 = $this->getFlatArray($arr1);

        $diff2 = $this->getFlatArray($arr2);

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

//        foreach ($exception->getTrace() as $err) {
//            $this->out('%s:%d', $makeRelative($err['file']??'', 2), $err['line']);
//        }
    }


    private function getFlatArray(array $arr): array
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

}
