<?php

use Sprint\Migration\SymfonyBundle\Command\ConsoleCommand;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Sprint\\Migration\\Controller',
        ],
        'readonly' => true,
    ],
    'console' => [
        'value' => [
            'commands' => [
                ConsoleCommand::class,
            ],
        ],
        'readonly' => true,
    ],
];
