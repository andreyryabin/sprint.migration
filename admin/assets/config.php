<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'css'       => './dist/index.bundle.css',
    'js'        => './dist/index.bundle.js',
    'rel'       => [
        'main.polyfill.core',
        'ui.vue3',
    ],
    'skip_core' => true,
    'lang'      => [
        removeDocRoot(__FILE__),
    ],
];
