<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\GraphQL\Scalars;

use GraphQL\Type\Definition\StringType;

class TranslatableString extends StringType
{
    public string $name = 'TranslatableString';
    public ?string $description = 'A string value that will have one or multiple translations';
}
