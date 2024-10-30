<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\DataObjects;

class Attribute
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $required,
        public string $directives = '',
    ) {
    }
}
