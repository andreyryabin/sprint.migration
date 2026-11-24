<?php

namespace Sprint\Migration\SymfonyBundle\Command;

use Sprint\Migration\Console;
use Sprint\Migration\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ConsoleCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('sprint:migration')
             ->setDescription('Migration console')
             ->ignoreValidationErrors();;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script = $_SERVER['argv'][0] . ' ' . $_SERVER['argv'][1];

        $args = [$script, ...array_slice($_SERVER['argv'], 2)];

        try {
            Module::checkHealth();
            $console = new Console($args);
            $console->executeConsoleCommand();
            return self::SUCCESS;
        } catch (Throwable) {
            return self::FAILURE;
        }
    }
}
