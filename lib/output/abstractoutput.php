<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractOutput implements OutputInterface
{
    public function __construct(private readonly ?LoggerInterface $logger = null) {}

    public function out(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, '');

        $this->logger?->debug($msg);
    }

    protected function resolve(string $msg, array $vars): string
    {
        return $vars ? vsprintf($msg, $vars) : $msg;
    }

    abstract protected function writeMessage(string $msg, string $type): void;

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->writeProgress($msg, $val, $total);

        $this->logger?->debug("$msg: $val / $total");
    }

    abstract protected function writeProgress(string $msg, int $val, int $total): void;

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

    public function outDiff(array $exists, array $fields): void
    {
        $exists1 = $this->getFlatArray($exists);

        $fields1 = $this->getFlatArray($fields);

        foreach (array_merge($exists1, $fields1) as $k => $v) {
            if (isset($exists1[$k]) && isset($fields1[$k])) {
                if ($exists1[$k] !== $fields1[$k]) {
                    $this->writeDiff('%s: [red]"%s"[/] -> [green]"%s"[/]', $k, $exists1[$k], $fields1[$k]);
                }
            } elseif (isset($exists1[$k])) {
                $this->writeDiff('%s: [red]"%s"[/]', $k, $exists1[$k]);
            } elseif (isset($fields1[$k])) {
                $this->writeDiff('%s: [green]"%s"[/]', $k, $fields1[$k]);
            }
        }
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

    public function outException(?Throwable $exception = null): void
    {
        if (!$exception) {
            return;
        }

        $this->outWarning(
            "[%s] %s (%s) in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $this->makeRelative($exception->getFile(), 2),
            $exception->getLine(),
        );
    }

    public function outWarning(string $msg, ...$vars): void
    {
        $msg = $this->resolve($msg, $vars);

        $this->writeMessage($msg, 'warning');

        $this->logger?->warning($msg);
    }

    private function makeRelative(string $path, int $depth = 0): string
    {
        $chunks = explode(DIRECTORY_SEPARATOR, $path);
        $chunks = array_slice($chunks, -($depth + 1));

        return '.../' . implode('/', $chunks);
    }
}
