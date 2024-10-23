<?php

declare(strict_types=1);

$stubDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs';

return [
    'stubs' => [
        'translations-field'       => $stubDirectory . DIRECTORY_SEPARATOR . 'translations-field.graphql-stub',
        'translations-input-field' => $stubDirectory . DIRECTORY_SEPARATOR . 'translations-input-field.graphql-stub',
    ],
];
