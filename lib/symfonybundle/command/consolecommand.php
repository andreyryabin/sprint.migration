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

    protected function getOptionsWithoutValues(): array
    {
        return [
            'new',
            'installed',
            'unknown',
            'modified',
            'older',
            'actual',
            'down',
        ];
    }

    protected function getOptionsWithValues(): array
    {
        return [
            'config',
            'as',
            'search',
            'tag',
            'add-tag',
        ];
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
                if (in_array($key, $this->getOptionsWithoutValues())) {
                    $args[] = '--' . $key;
                } else {
                    $args[] = '--' . $key . '=' . $val;
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
}
