<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\DataObjects;

readonly class Attribute
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $required,
        public string $directives = '',
    ) {
    }
}
