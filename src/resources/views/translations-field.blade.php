{{--
    You can customize the translations attribute that the translatable directive appends to the
    root type through this stub.

    For instance, you might want to add a `@hasMany` directive to the generated output to optimize
    query performance. You could do that by changing this blade file to:

        {{ $attributeName }}: [{{$translationTypeName}}!]! @hasMany

    Available variables:
    $attributeName: The name of the attribute to be generated. This defaults to "translations"
    $translationTypeName: The name of the translation type. For instance "NewsItemTranslation".
--}}
{{$attributeName}}: [{{$translationTypeName}}!]!
