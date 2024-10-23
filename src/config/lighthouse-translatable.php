<?php

declare(strict_types=1);

return [
    'stub-directory' => env('LIGHTHOUSE_TRANSLATABLE_STUBS_DIRECTORY', base_path('vendor/dennis-koster/lighthouse-translatable/src/stubs')),
    'stubs'          => [
        'translations-field'       => 'translations-field.graphql-stub',
        'translations-input-field' => 'translations-input-field.graphql-stub',
    ],
];
