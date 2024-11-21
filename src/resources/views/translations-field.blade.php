{{--
    You can customize the translations attribute that the translatable directive appends to the
    root type through this stub.

    For instance, you might want to add a `@hasMany` directive to the generated output to optimize
    query performance. You could do that by changing this blade file to:

        {{ $attributeName }}: [{{$translationTypeName}}!]! @hasMany

    --------------------------------------------------

    type NewsItem @translatable {
        fooBar: TranslatableString! @rename(attribute: "foo_bar") <--- Directives available in $directive variable
    }

    Results in:

    type NewsItem {
        fooBar: String!
        translations: [NewsItemTranslation!]! <---- Affected by this template
    }

    --------------------------------------------------

    Available variables:
    $attributeName: The name of the attribute to be generated. This defaults to "translations"
    $translationTypeName: The name of the translation type. For instance "NewsItemTranslation".
--}}
{{$attributeName}}: [{{$translationTypeName}}!]!
