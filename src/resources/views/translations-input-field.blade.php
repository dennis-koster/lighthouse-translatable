{{--
    You can customize the translations attribute that the translatable directive appends to the
    input types, specified through the "appendInput" argument, through this stub. For instance,
    you might want to make the translations attribute nullable. You could do that by changing
    this blade file to:
        {{ $attributeName }}: [{{$translationInputTypeName}}]

    $attributeName: The name of the attribute to be generated. This defaults to "translations"
    $translationTypeName: The name of the translation type. For instance "NewsItemTranslation".
--}}
{{$attributeName}}: [{{$translationInputTypeName}}!]!
