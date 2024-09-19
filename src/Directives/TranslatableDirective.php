<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Directives;

use DennisKoster\LighthouseTranslatable\DataObjects\DirectiveArguments;
use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Events\Dispatcher;
use Nuwave\Lighthouse\Events\ManipulateAST;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

class TranslatableDirective extends BaseDirective implements TypeManipulator
{
    public function __construct(
        protected TypeRegistry $typeRegistry,
        protected Dispatcher $eventDispatcher,
    ) {
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generates translations attribute for the type.
"""
directive @translatable(
    """
    Whether or not to generate a type for the translation model. Defaults to true.
    """
    generateTranslationType: Boolean! = true
    """
    The name of the type to be generated for the translation model. Defaults to "<BaseType>Translation".
    """
    translationTypeName: String! = "<BaseType>Translation"
    """
    The name of the attribute that holds the array of translation models. Defaults to "translations".
    """
    translationsAttribute: String! = "translations"
    """
    Whether or not to generate a type for the translation model input. Defaults to true.
    """
    generateInputType: Boolean! = true
    """
    The name of the type to be generated for the translation model input. Defaults to "<BaseType>TranslationInput".
    """
    inputTypeName: String!  = "<BaseType>TranslationInput"
    """
    The inputs the translation model input should be appended to. Defaults to an empty array.
    """
    appendInput: [String!]! = []
) on OBJECT
SDL;
    }

    public function manipulateTypeDefinition(
        DocumentAST &$documentAST,
        TypeDefinitionNode &$typeDefinition
    ): void {
        if (! $typeDefinition instanceof ObjectTypeDefinitionNode) {
            return;
        }

        $typeName           = $typeDefinition->getName()->value;
        $directiveArguments = $this->getDirectiveArguments($typeName);
        $translatableFields = $this->extractTranslatableFields($typeDefinition->fields);

        if (empty($translatableFields)) {
            return;
        }

        if ($directiveArguments->generateTranslationType) {
            $this->registerTranslationType($directiveArguments, $typeDefinition, $translatableFields);
        }

        if ($directiveArguments->generateInputType) {
            $this->registerTranslationInputType($directiveArguments, $translatableFields);

            if (! empty($directiveArguments->appendInput)) {
                $inputValueDefinition = $this->getTranslationsInputValueDefinition($directiveArguments);

                $this->appendToInputTypes($inputValueDefinition, $directiveArguments->appendInput);
            }
        }
    }

    protected function getTranslationsFieldDefinition(
        DirectiveArguments $directiveArguments,
    ): FieldDefinitionNode {
        return new FieldDefinitionNode([
            'name'       => new NameNode([
                'value' => $directiveArguments->translationsAttributeName,
            ]),
            'type'       => $this->getTypeDefinitionForTranslations($directiveArguments->translationTypeName),
            'directives' => new NodeList([]),
            'arguments'  => new NodeList([]),
        ]);
    }

    protected function getTranslationsInputValueDefinition(
        DirectiveArguments $directiveArguments,
    ): InputValueDefinitionNode {
        return new InputValueDefinitionNode([
            'name'       => new NameNode([
                'value' => $directiveArguments->translationsAttributeName,
            ]),
            'type'       => $this->getTypeDefinitionForTranslations($directiveArguments->inputTypeName),
            'directives' => new NodeList([]),
        ]);
    }

    /**
     * Returns the type definition for the following string representation:
     * [$typeName!]!
     *
     * @param string $typeName
     * @return NonNullTypeNode
     */
    protected function getTypeDefinitionForTranslations(
        string $typeName,
    ): NonNullTypeNode {
        return new NonNullTypeNode([
            'type' => new ListTypeNode([
                'type' => new NonNullTypeNode([
                    'type' => new NamedTypeNode([
                        'name' => new NameNode([
                            'value' => $typeName,
                        ]),
                    ]),
                ]),
            ]),
        ]);
    }

    protected function getDirectiveArguments(
        string $rootTypeName,
    ): DirectiveArguments {
        return new DirectiveArguments(
            $this->directiveArgValue('translationTypeName', "{$rootTypeName}Translation"),
            $this->directiveArgValue('inputTypeName', "{$rootTypeName}TranslationInput"),
            $this->directiveArgValue('translationsAttributeName', 'translations'),
            $this->directiveArgValue('generateTranslationType', true),
            $this->directiveArgValue('generateInputType', true),
            $this->directiveArgValue('appendInput', []),
        );
    }

    /**
     * Loop over the list of fields and find any attribute
     * of the TranslatableString scalar type.
     *
     * @param NodeList<FieldDefinitionNode> $fields
     * @return array<string, array<string, Type>>
     */
    protected function extractTranslatableFields(NodeList $fields): array
    {
        $translatableFields = [];

        foreach ($fields as $field) {
            $fieldType = $field->type;
            $fieldName = $field->name->value;

            if (ASTHelper::getUnderlyingTypeName($fieldType) !== (new TranslatableString)->name) {
                continue;
            }

            $required = $fieldType instanceof NonNullTypeNode;
            $type     = $required
                ? Type::nonNull(Type::string())
                : Type::string();

            $translatableFields[$fieldName] = [
                'type' => $type,
            ];
        }

        if (! empty($translatableFields)) {
            $translatableFields['locale'] = [
                'type' => Type::nonNull(Type::String()),
            ];
        }

        return $translatableFields;
    }

    /**
     * @param DirectiveArguments $directiveArguments
     * @param ObjectTypeDefinitionNode $rootType
     * @param array<string, array<string, Type>> $translatableFields
     * @return void
     * @throws \Nuwave\Lighthouse\Exceptions\DefinitionException
     */
    protected function registerTranslationType(
        DirectiveArguments $directiveArguments,
        ObjectTypeDefinitionNode &$rootType,
        array $translatableFields,
    ): void {
        $object = new ObjectType([
            'name'   => $directiveArguments->translationTypeName,
            'fields' => $translatableFields,
        ]);

        $this->typeRegistry->register(
            $object,
        );

        $rootType->fields = ASTHelper::prepend(
            $rootType->fields,
            $this->getTranslationsFieldDefinition($directiveArguments)
        );
    }

    /**
     * @param DirectiveArguments $directiveArguments
     * @param array<string, array<string, Type>> $translatableFields
     * @return void
     * @throws \Nuwave\Lighthouse\Exceptions\DefinitionException
     */
    protected function registerTranslationInputType(DirectiveArguments $directiveArguments, array $translatableFields): void
    {
        $this->typeRegistry->register(
            new InputObjectType([
                'name'   => $directiveArguments->inputTypeName,
                'fields' => $translatableFields,
            ])
        );
    }

    protected function appendToInputTypes(
        InputValueDefinitionNode $inputValueDefinition,
        array $inputTypes = []
    ): void {
        $this->eventDispatcher->listen(
            ManipulateAST::class,
            function (
                ManipulateAST $event
            ) use (
                $inputTypes,
                $inputValueDefinition
            ) {
                $types = $event->documentAST->types;

                foreach ($inputTypes as $appendInputTypes) {
                    if (! array_key_exists($appendInputTypes, $types)) {
                        continue;
                    }

                    $inputType = $types[$appendInputTypes];

                    $inputType->fields = ASTHelper::prepend(
                        $inputType->fields,
                        $inputValueDefinition
                    );
                }
            }
        );
    }
}
