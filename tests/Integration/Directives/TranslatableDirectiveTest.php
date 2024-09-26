<?php

declare(strict_types=1);

namespace Tests\Integration\Directives;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\AbstractIntegrationTestCase;
use Tests\Utilities\Traits\SchemaAssertions;

class TranslatableDirectiveTest extends AbstractIntegrationTestCase
{
    use SchemaAssertions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestSchema();
    }

    #[Test]
    public function it_generates_translation_type_and_input_type_with_the_default_settings(): void
    {
        $schema = $this->getSchema();

        static::assertTrue($schema->hasType('NewsItemTranslation'));
        static::assertTrue($schema->hasType('NewsItemTranslationInput'));

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                translations: [NewsItemTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
            }'
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItemTranslation {
                title: String!
                description: String
                locale: String!
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input NewsItemTranslationInput {
                title: String!
                description: String
                locale: String!
            }',
        );
    }

    #[Test]
    public function it_generates_translation_type_and_input_type_with_a_custom_name(): void
    {
        $schema = $this->getSchema('@translatable(
            translationTypeName: "FooBarTranslation"
            inputTypeName: "FooBarInput"
        )');

        static::assertTrue($schema->hasType('FooBarTranslation'));
        static::assertTrue($schema->hasType('FooBarInput'));

        $schemaString = SchemaPrinter::doPrint($schema);

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                translations: [FooBarTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type FooBarTranslation {
              title: String!
              description: String
              locale: String!
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooBarInput {
              title: String!
              description: String
              locale: String!
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_an_input_type(): void
    {
        $schema = $this->getSchema('@translatable(
            generateInputType: false
        )');

        static::assertFalse($schema->hasType('NewsItemTranslationInput'));

        static::assertSchemaNotContains(
            $schema,
            /** @lang GraphQL */ '
            input NewsItemTranslationInput {
              title: String!
              description: String
              locale: String!
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_translation_type(): void
    {
        $schema = $this->getSchema('@translatable(generateTranslationType: false)');

        static::assertFalse($schema->hasType('NewsItemTranslation'));

        static::assertSchemaNotContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItemTranslation {
                title: String!
                description: String
                locale: String!
            }',
        );
    }

    #[Test]
    public function it_uses_a_custom_name_for_the_translations_attribute(): void
    {
        $schema = $this->getSchema('@translatable(translationsAttribute: "alternativeLanguages")');

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                alternativeLanguages: [NewsItemTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
            }',
        );
    }

    #[Test]
    public function it_appends_the_generated_input_type_to_provided_existing_input_types(): void
    {
        $existingTypes = <<<GRAPHQL
input FooInput {
    foo: String!
}

input BarInput {
    bar: String!
}
GRAPHQL;

        $schema = $this->getSchema(
            '@translatable(appendInput: ["FooInput", "BarInput"])',
            $existingTypes,
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooInput {
                translations: [NewsItemTranslationInput!]!
                foo: String!
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input BarInput {
                translations: [NewsItemTranslationInput!]!
                bar: String!
            }',
        );
    }

    protected function getSchema(
        string $directive = '@translatable',
        string $additionalSchemaDefinitions = '',
    ): Schema {
        $schema = <<<GRAPHQL
type NewsItem {$directive} {
    id: ID!
    title: TranslatableString!
    description: TranslatableString
    slug: String!
}
{$additionalSchemaDefinitions}
GRAPHQL;

        return $this->buildSchema($schema);
    }
}
