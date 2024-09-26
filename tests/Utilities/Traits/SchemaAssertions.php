<?php

declare(strict_types=1);

namespace Tests\Utilities\Traits;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

trait SchemaAssertions
{
    use StringAssertions;

    public static function assertSchemaContains(Schema $schema, string $needle): void
    {
        static::assertStringContainsSubstringIgnoringLeadingWhitespace(
            $needle,
            SchemaPrinter::doPrint($schema),
        );
    }

    public static function assertSchemaNotContains(Schema $schema, string $needle): void
    {
        static::assertStringNotContainsSubstringIgnoringLeadingWhitespace(
            $needle,
            SchemaPrinter::doPrint($schema),
        );
    }
}
