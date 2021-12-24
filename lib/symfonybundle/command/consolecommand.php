<?php

namespace Sprint\Migration\SymfonyBundle\Command;

use Sprint\Migration\Console;
use Sprint\Migration\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ConsoleCommand extends Command
{
    protected function configure()
    {
        $this->setName('sprint:migration')
             ->setDescription('Migration console');

        foreach ($this->getArguments() as $name) {
            $this->addArgument($name);
        }

        foreach ($this->getOptionsWithoutValues() as $name) {
            $this->addOption($name);
        }
        foreach ($this->getOptionsWithValues() as $name) {
            $this->addOption($name, null, InputOption::VALUE_REQUIRED);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = [];
        foreach ($input->getArguments() as $val) {
            if ($val) {
                $args[] = $val;
            }
        }

        foreach ($input->getOptions() as $key => $val) {
            if ($val) {
                if (in_array($key, $this->getOptionsWithValues())) {
                    $args[] = '--' . $key . '=' . $val;
                } else {
                    $args[] = '--' . $key;
                }
            }
        }
        try {
            Module::checkHealth();
            $console = new Console($args);
            $console->executeConsoleCommand();
            return self::SUCCESS;
        } catch (Throwable $e) {
            return self::FAILURE;
        }
    }

    protected function getOptionsWithValues(): array
    {
        return [
            'desc',
            'config',
            'prefix',
            'name',
            'from',
            'as',
            'search',
            'tag',
            'add-tag',
        ];
    }

    protected function getOptionsWithoutValues(): array
    {
        return [
            'new',
            'installed',
            'modified',
            'older',
            'down',
            'force',
            'skip-errors',
            'stop-on-errors',
        ];
    }

    protected function getArguments(): array
    {
        return [
            'arg1',
            'arg2',
            'arg3',
            'arg4',
            'arg5',
        ];
    }
}
