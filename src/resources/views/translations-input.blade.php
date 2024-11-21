{{--
    You can customize the translations input definition that the translatable directive appends to the
    input types, specified through the "appendInput" argument, through this stub.

    For instance, you might want to make the translations input nullable. You could do that
    by changing this blade file to:

        {{ $attributeName }}: [{{$translationInputTypeName}}]

    --------------------------------------------------

    type NewsItem @translatable(appendInput: ["NewsItemInput"]) {
        fooBar: TranslatableString! @rename(attribute: "foo_bar") <--- Directives available in $directive variable
    }

    input NewsItemInput {
        publicationDate: DateTime!
    }

    Results in:

    input NewsItemInput {
        publicationDate: DateTime!
        translations: [NewsItemTranslationInput!]! <---- Affected by this template
    }

    --------------------------------------------------

    Available variables:
    $name: The name of the attribute to be generated. This defaults to "translations"
    $type: The name of the translation type. For instance "NewsItemTranslationInput".
--}}
{{$name}}: [{{$type}}!]!
