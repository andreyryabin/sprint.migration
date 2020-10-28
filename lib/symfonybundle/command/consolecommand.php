<?php

namespace Sprint\Migration\SymfonyBundle\Command;

use Exception;
use Sprint\Migration\Console;
use Sprint\Migration\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends Command
{
    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws Exception
     * @return null|int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
        Module::checkHealth();
        $console = new Console($args);
        $console->executeConsoleCommand();
    }

    protected function getOptionsWithValues()
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

    protected function getOptionsWithoutValues()
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

    protected function getArguments()
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
