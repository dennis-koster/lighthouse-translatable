# Lighthouse Translatable
This package will make generation of GraphQL types for translatable type definitions a breeze. It ships the 
`@translatable` directive, which can be applied to any GraphQL type definition.

The directive will look for any attributes of the `TranslatableString` scalar type, also included in this package, and 
include it in the types it generates.
It will respect nullable states of attributes when generating the type definitions.

The directive can generate two types of GraphQL types, based off the main type the directive gets applied to:
* A translation type: an individual type definition containing all the attributes that are translatable, along with its locale
* An input type: an input definition, containing all the attributes that are translatable, along with its locale

## Installation
The package can be installed through composer.
```shell
composer require dennis-koster/lighthouse-translatable
```

## Package compatibility
The package is completely agnostic of any translations package you might use for saving and retrieving translations to
and from the database. This package focuses solely on generating schema definitions.

Its only dependency is on `nuwave/lighthouse` version `6` or higher.

## Arguments
The directive comes with a couple of arguments, all of them optional.

| Argument                     | Description                                                                | Default                              |
|------------------------------|----------------------------------------------------------------------------|--------------------------------------|
| **generateTranslationType**  | Whether or not to generate a type for the translation definition.          | Boolean: `True`                      |
| **translationTypeName**      | The name of the type to be generated for the translation definition.       | String: `<BaseType>Translation`      |
| **translationsAttribute**    | The name of the attribute that holds the array of translations.            | String: `translations`               |
| **generateInputType**        | Whether or not to generate a type for the translation input definition.    | Boolean: `True`                      |
| **inputTypeName**            | The name of the type to be generated for the translation input definition. | String: `<BaseType>TranslationInput` |
| **appendInput**              | The inputs the translation model input should be appended to.              | Array: []                            |

## Basic usage
In its most primal form, the directive uses some sensible defaults. It will always generate a translation definition and 
a translation input definition for the type the directive is applied to.

It will take the name of the base type, in the example below `NewsItem` and append `Translation` (so `NewsItemTranslation`) 
for the translation definition, and `TranslationInput` for the translation input definition (`NewsItemTranslationInput`).

An attribute will be added to the base type definition, holding the translation definitions. By default, the attribute
will be called `translations` but this is customizable through the `translationsAttribute` argument.

```graphql
type NewsItem @translatable
{
    id: ID!
    title: TranslatableString!
    introduction: TranslatableString
}

# Will result in the following schema definition
type NewsItem
{
    id: ID!
    title: TranslatableString!
    introduction: TranslatableString
    translations: [NewsItemTranslation!]! # Added through the directive
}

type NewsItemTranslation {
    locale: String!
    title: String!
    introduction: String
}

input NewsItemTranslationInput {
    locale: String!
    title: String!
    introduction: String
}
```

## Append input type to existing inputs

It's possible to provide the directive an array of existing inputs you want to append a translations argument to,
through the `appendInput` argument.

The attribute will be called `translations` by default, and can be changed through the `translationsAttribute` argument.

```graphql
type NewsItem @translatable(appendInput: ["CreateNewsItemInput"])
{
    id: ID!
    slug: String!
    title: TranslatableString!
    introduction: TranslatableString
}

input CreateNewsItemInput {
    slug: String!
}

# Will append the translations attribute to the CreateNewsItemInput
input CreateNewsItemInput {
    slug: String!
    translations: [NewsItemTranslation!]!
}
```

## Customize type names and attribute name
It's possible to customize the names of the type definitions that are generated, as well as the name
of the attribute it appends to existing type definitions.

```graphql
type NewsItem @translatable(
    translationTypeName: "FooBarTranslation"
    inputTypeName: "FooBarTranslationInput"
    translationsAttribute: "localizations"
    appendInput: ["CreateNewsItemInput"]
) {
    id: ID!
    title: TranslatableString!
    introduction: TranslatableString
}

input CreateNewsItemInput {
    slug: String!
}

# Will result in the following schema definition
type NewsItem
{
    id: ID!
    title: TranslatableString!
    introduction: TranslatableString
    localizations: [FooBarTranslation!]!
}

input CreateNewsItemInput {
    slug: String!
    localizations: [FooBarTranslationInput!]!
}

type FooBarTranslation {
    locale: String!
    title: String!
    introduction: String
}

input FooBarTranslationInput {
    locale: String!
    title: String!
    introduction: String
}
```

## Configuration
The directive comes with a configuration file that can be published through:
```shell
php artisan vendor:publish --provider=DennisKoster\\LighthouseTranslatable\\Providers\\LighthouseTranslatableProvider
```

Through the configuration file, the stub file locations can be altered. 
