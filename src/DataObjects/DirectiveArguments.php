<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\DataObjects;

readonly class DirectiveArguments
{
    public function __construct(
        public string $translationTypeName,
        public string $inputTypeName,
        public string $translationsFieldName,
        public string $translationsInputName,
        public bool $generateTranslationType = true,
        public bool $generateInputType = true,
        public array $appendInput = [],
    ) {
    }
}
