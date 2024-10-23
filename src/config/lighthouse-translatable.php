<?php

declare(strict_types=1);

$stubDirectory = base_path('vendor/dennis-koster/lighthouse-translatable/src/stubs');

return [
    'stubs' => [
        'translations-field'       => $stubDirectory . DIRECTORY_SEPARATOR . 'translations-field.graphql-stub',
        'translations-input-field' => $stubDirectory . DIRECTORY_SEPARATOR . 'translations-input-field.graphql-stub',
    ],
];
