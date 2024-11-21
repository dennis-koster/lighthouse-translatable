<?php

declare(strict_types=1);

namespace Tests\Integration\Directives;

use GraphQL\Type\Schema;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\AbstractIntegrationTestCase;
use Tests\Utilities\Traits\SchemaAssertions;

class TranslatableDirectiveTest extends AbstractIntegrationTestCase
{
    use SchemaAssertions;
    use MocksResolvers;

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
                fooBar: TranslatableString
            }'
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItemTranslation {
                title: String!
                description: String
                fooBar: String                                
                locale: String!                
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input NewsItemTranslationInput {
                title: String!
                description: String
                fooBar: String                                
                locale: String!  
            }',
        );
    }

    #[Test]
    public function it_generates_translation_type_and_input_type_with_a_custom_name_provided_through_the_directive(): void
    {
        $schema = $this->getSchema('@translatable(
            translationTypeName: "FooBarTranslation"
            inputTypeName: "FooBarInput"
        )');

        static::assertTrue($schema->hasType('FooBarTranslation'));
        static::assertTrue($schema->hasType('FooBarInput'));

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                translations: [FooBarTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
                fooBar: TranslatableString
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type FooBarTranslation {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooBarInput {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_generates_translation_type_and_input_type_with_a_custom_name_provided_through_the_config(): void
    {
        $this->app['config']->set('lighthouse-translatable.directive-defaults.translation-type-name', 'FooBarTranslation');
        $this->app['config']->set('lighthouse-translatable.directive-defaults.input-type-name', 'FooBarInput');

        $schema = $this->getSchema();

        static::assertTrue($schema->hasType('FooBarTranslation'));
        static::assertTrue($schema->hasType('FooBarInput'));

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                translations: [FooBarTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
                fooBar: TranslatableString
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type FooBarTranslation {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooBarInput {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_an_input_type_if_specified_through_the_directive(): void
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
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_an_input_type_if_specified_through_the_config(): void
    {
        $this->app['config']->set('lighthouse-translatable.directive-defaults.generate-input-type', false);

        $schema = $this->getSchema();

        static::assertFalse($schema->hasType('NewsItemTranslationInput'));

        static::assertSchemaNotContains(
            $schema,
            /** @lang GraphQL */ '
            input NewsItemTranslationInput {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_translation_type_if_specified_through_the_directive(): void
    {
        $schema = $this->getSchema('@translatable(generateTranslationType: false)');

        static::assertFalse($schema->hasType('NewsItemTranslation'));

        static::assertSchemaNotContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItemTranslation {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_does_not_generate_translation_type_if_specified_through_the_config(): void
    {
        $this->app['config']->set('lighthouse-translatable.directive-defaults.generate-translation-type', false);

        $schema = $this->getSchema();

        static::assertFalse($schema->hasType('NewsItemTranslation'));

        static::assertSchemaNotContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItemTranslation {
                title: String!
                description: String
                fooBar: String                                
                locale: String!    
            }',
        );
    }

    #[Test]
    public function it_uses_a_custom_name_for_the_translations_field_specified_through_the_directive(): void
    {
        $schema = $this->getSchema('@translatable(translationsFieldName: "alternativeLanguages")');

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                alternativeLanguages: [NewsItemTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
                fooBar: TranslatableString
            }',
        );
    }

    #[Test]
    public function it_uses_a_custom_name_for_the_translations_field_specified_through_the_config(): void
    {
        $this->app['config']->set('lighthouse-translatable.directive-defaults.translations-field-name', 'alternativeLanguages');

        $schema = $this->getSchema();

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            type NewsItem {
                alternativeLanguages: [NewsItemTranslation!]!
                id: ID!
                title: TranslatableString!
                description: TranslatableString
                slug: String!
                fooBar: TranslatableString
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

    #[Test]
    public function it_uses_a_custom_name_for_the_translations_input_specified_through_the_directive(): void
    {
        $existingTypes = <<<GRAPHQL
input FooInput {
    foo: String!
}
GRAPHQL;

        $schema = $this->getSchema(
            '@translatable(appendInput: ["FooInput"], translationsInputName: "alternativeLanguages")',
            $existingTypes,
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooInput {
                alternativeLanguages: [NewsItemTranslationInput!]!
                foo: String!
            }',
        );
    }

    #[Test]
    public function it_uses_a_custom_name_for_the_translations_input_specified_through_the_config(): void
    {
        $this->app['config']->set('lighthouse-translatable.directive-defaults.translations-input-name', 'alternativeLanguages');

        $existingTypes = <<<GRAPHQL
input FooInput {
    foo: String!
}
GRAPHQL;

        $schema = $this->getSchema(
            '@translatable(appendInput: ["FooInput"])',
            $existingTypes,
        );

        static::assertSchemaContains(
            $schema,
            /** @lang GraphQL */ '
            input FooInput {
                alternativeLanguages: [NewsItemTranslationInput!]!
                foo: String!
            }',
        );
    }

    #[Test]
    public function it_copies_the_directives_from_the_main_type_to_the_translation_types(): void
    {
        $this->mockResolver(function ($root, array $args) {
            static::assertSame('Testing', $args['input']['translations'][0]['foo_bar']);

            return $args['input'];
        });

        $query = <<<GRAPHQL
input NewsItemInput {
    foo: String
}

type Mutation {
    createNewsItem(input: NewsItemInput!): NewsItem! @mock
}
GRAPHQL;

        $this->getSchema(
            '@translatable(appendInput: ["NewsItemInput"])',
            $query,
        );

        $mutation = <<<GRAPHQL
mutation {
    createNewsItem(input: {
        translations: [{
            title: "Title"
            fooBar: "   Testing  "
            locale: "en"
        }]
    }) {
        translations {
            fooBar
        }
    }
}
GRAPHQL;

        $this->graphQL($mutation);
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
    fooBar: TranslatableString 
        @trim
        @rename(attribute: "foo_bar")
}
{$additionalSchemaDefinitions}
GRAPHQL;

        return $this->buildSchema($schema);
    }
}
