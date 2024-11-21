{{--
    This template is used for the attributes that are automatically generated for the translation type.

    --------------------------------------------------

    type NewsItem @translatable {
        fooBar: TranslatableString! @rename(attribute: "foo_bar") <--- Directives available in $directive variable
    }

    Results in:

    type NewsItemTranslationInput {
        locale: String!
        fooBar: String! @rename(attribute: "foo_bar") <---- Affected by this template
    }

    --------------------------------------------------

    Available variables:
    $name: The name of the attribute.
    $type: The type of the attribute
    $required: Whether the field is required (non-nullable)
    $directives: A string containing all the directives copied from the TranslatableString typed attribute on root type
    $translationTypeName: The name of the translation type. For instance "NewsItemTranslation".
--}}
{{ $name }}: {{ $type }}{{ $required ? '!' : '' }} {!! $directives !!}
