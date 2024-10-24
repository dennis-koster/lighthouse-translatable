<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Parsers;

use DennisKoster\LighthouseTranslatable\DataObjects\Attribute;
use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use GraphQL\Type\Definition\StringType;
use Illuminate\Support\Str;

class FieldDefinitionStringParser
{
    public function parse(string $fieldDefinitionString): Attribute
    {
        $attributeParts = explode(':', $fieldDefinitionString);
        $name           = $attributeParts[0];

        $definition = trim(Str::after($fieldDefinitionString, "{$name}:"));
        $type       = Str::before($definition, ' ');
        $directives = trim(Str::after($definition, $type));
        $required   = Str::contains($type, '!');
        $type       = Str::replaceFirst('!', '', $type);
        $type       = $this->mapToNativeType($type);

        return new Attribute(
            $name,
            $type,
            $required,
            $directives,
        );
    }

    protected function mapToNativeType(string $type): string
    {
        return match ($type) {
            (new TranslatableString)->name => (new StringType)->name,
            default                        => $type,
        };
    }
}
