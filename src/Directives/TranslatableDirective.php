<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Directives;

use DennisKoster\LighthouseTranslatable\DataObjects\DirectiveArguments;
use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
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
        protected Factory $viewFactory,
        protected Config $config,
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
    Whether or not to generate a type for the translation definition. Defaults to true.
    """
    generateTranslationType: Boolean! = true
    """
    The name of the type to be generated for the translation definition. Defaults to "<BaseType>Translation".
    """
    translationTypeName: String! = "<BaseType>Translation"
    """
    The name of the attribute that holds the array of translations. Defaults to "translations".
    """
    translationsAttribute: String! = "translations"
    """
    Whether or not to generate a type for the translation input definition. Defaults to true.
    """
    generateInputType: Boolean! = true
    """
    The name of the type to be generated for the translation input definition. Defaults to "<BaseType>TranslationInput".
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
            $this->registerTranslationType($directiveArguments, $translatableFields);

            $typeDefinition->fields = ASTHelper::prepend(
                $typeDefinition->fields,
                $this->getTranslationsFieldDefinition($directiveArguments)
            );
        }

        if ($directiveArguments->generateInputType) {
            $this->registerTranslationInputType($directiveArguments, $translatableFields);

            if (! empty($directiveArguments->appendInput)) {
                $inputValueDefinition = $this->getTranslationsInputValueDefinition($directiveArguments);

                $this->appendToInputTypes($inputValueDefinition, $directiveArguments->appendInput);
            }
        }
    }

    /**
     * @param array<string, array<string, Type>> $translatableFields
     */
    protected function registerTranslationType(
        DirectiveArguments $directiveArguments,
        array $translatableFields,
    ): void {
        $this->typeRegistry->register(
            new ObjectType([
                'name'   => $directiveArguments->translationTypeName,
                'fields' => $translatableFields,
            ]),
        );
    }

    /**
     * @param array<string, array<string, Type>> $translatableFields
     */
    protected function registerTranslationInputType(
        DirectiveArguments $directiveArguments,
        array $translatableFields
    ): void {
        $this->typeRegistry->register(
            new InputObjectType([
                'name'   => $directiveArguments->inputTypeName,
                'fields' => $translatableFields,
            ]),
        );
    }

    /**
     * @param array<int, string> $inputTypes
     */
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

    protected function getTranslationsFieldDefinition(
        DirectiveArguments $directiveArguments,
    ): FieldDefinitionNode {
        $template = $this->viewFactory
            ->file($this->getStubFilePath('translations-field'))
            ->with([
                'attributeName'       => $directiveArguments->translationsAttributeName,
                'translationTypeName' => $directiveArguments->translationTypeName,
            ])
            ->render();

        return Parser::fieldDefinition($template);
    }

    protected function getTranslationsInputValueDefinition(
        DirectiveArguments $directiveArguments,
    ): InputValueDefinitionNode {
        $template = $this->viewFactory
            ->file($this->getStubFilePath('translations-input-field'))
            ->with([
                'attributeName'            => $directiveArguments->translationsAttributeName,
                'translationInputTypeName' => $directiveArguments->inputTypeName,
            ])
            ->render();

        return Parser::inputValueDefinition($template);
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
                'type'       => $type,
                'directives' => $field->directives,
            ];
        }

        if (! empty($translatableFields)) {
            $translatableFields['locale'] = [
                'type' => Type::nonNull(Type::String()),
            ];
        }

        return $translatableFields;
    }

    protected function getDirectiveArguments(
        string $rootTypeName,
    ): DirectiveArguments {
        return new DirectiveArguments(
            $this->directiveArgValue('translationTypeName', "{$rootTypeName}Translation"),
            $this->directiveArgValue('inputTypeName', "{$rootTypeName}TranslationInput"),
            $this->directiveArgValue('translationsAttribute', 'translations'),
            $this->directiveArgValue('generateTranslationType', true),
            $this->directiveArgValue('generateInputType', true),
            $this->directiveArgValue('appendInput', []),
        );
    }

    protected function getStubFilePath(string $stubConfiguration): string
    {
        return $this->config->get('lighthouse-translatable.stub-directory')
            . DIRECTORY_SEPARATOR
            . $this->config->get("lighthouse-translatable.stubs.{$stubConfiguration}");
    }
}
