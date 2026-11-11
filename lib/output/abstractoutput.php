<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;

abstract class AbstractOutput implements OutputInterface
{
    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    abstract protected function writeMessage(string $msg, string $type): void;

    abstract protected function writeProgress(string $msg, int $val, int $total): void;

    public function out(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, '');

        $this->logger?->debug($msg);
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->writeProgress($msg, $val, $total);

        $this->logger?->debug($msg . ' ' . $val . ' / ' . $total);
    }

    public function outInfo(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'info');

        $this->logger?->info($msg);
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'success');

        $this->logger?->notice($msg);
    }

    public function outNotice(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'notice');

        $this->logger?->notice($msg);
    }

    public function outError(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'error');

        $this->logger?->error($msg);
    }

    public function outWarning(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'warning');

        $this->logger?->warning($msg);
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        $diff1 = $this->getFlatArray($arr1);

        $diff2 = $this->getFlatArray($arr2);

        $diff = array_merge($diff1, $diff2);

        foreach ($diff as $k => $v) {
            if (isset($diff1[$k]) && isset($diff2[$k])) {
                if ($diff1[$k] !== $diff2[$k]) {
                    $this->writeDiff('%s: [red]%s[/] -> [green]%s[/]', $k, $diff2[$k], $diff1[$k]);
                }
            } elseif (isset($diff1[$k])) {
                $this->writeDiff('%s: [green]%s[/]', $k, $diff1[$k]);
            } elseif (isset($diff2[$k])) {
                $this->writeDiff('%s: [red]%s[/]', $k, $diff2[$k]);
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

    protected function resolve(string $msg, array $vars): string
    {
        return $vars ? vsprintf($msg, $vars) : $msg;
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

    private function writeDiff(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, '');

        //without log
    }
}
