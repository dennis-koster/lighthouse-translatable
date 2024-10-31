<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Directives;

use DennisKoster\LighthouseTranslatable\DataObjects\Attribute;
use DennisKoster\LighthouseTranslatable\DataObjects\DirectiveArguments;
use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use DennisKoster\LighthouseTranslatable\Parsers\FieldDefinitionStringParser;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
        protected FieldDefinitionStringParser $fieldDefinitionStringParser,
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
        $typeDefinition     = $this->validatedTypeDefinition($typeDefinition);
        $typeName           = $typeDefinition->getName()->value;
        $directiveArguments = $this->getDirectiveArguments($typeName);

        if ($directiveArguments->generateTranslationType) {
            // Generate and register the type definition for the <Model>Translation type
            $translationTypeDefinition = $this->makeTranslationType($directiveArguments, $typeDefinition);
            $documentAST->setTypeDefinition($translationTypeDefinition);

            // Append the "translations" attribute to the root type
            $typeDefinition->fields = ASTHelper::prepend(
                $typeDefinition->fields,
                $this->makeTranslationsField($directiveArguments)
            );
        }

        if ($directiveArguments->generateInputType) {
            // Generate and register the input type definition for the <Model>TranslationInput type
            $translationTypeDefinition = $this->makeInputType($directiveArguments, $typeDefinition);
            $documentAST->setTypeDefinition($translationTypeDefinition);

            if (! empty($directiveArguments->appendInput)) {
                $inputValueDefinition = $this->makeTranslationsInput($directiveArguments);

                $this->appendToInputTypes($inputValueDefinition, $directiveArguments->appendInput);
            }
        }
    }

    /**
     * Makes the object type definition for <BaseType>Translation
     */
    protected function makeTranslationType(
        DirectiveArguments $directiveArguments,
        ObjectTypeDefinitionNode $typeDefinition,
    ): ObjectTypeDefinitionNode {
        return new ObjectTypeDefinitionNode([
            'name'       => new NameNode(['value' => $directiveArguments->translationTypeName]),
            'fields'     => new NodeList($this->getTranslatableFields($typeDefinition)),
            'interfaces' => new NodeList([]),
            'directives' => new NodeList([]),
        ]);
    }

    /**
     * Makes the input object type for <BaseType>TranslationInput
     */
    protected function makeInputType(
        DirectiveArguments $directiveArguments,
        ObjectTypeDefinitionNode $typeDefinition,
    ): InputObjectTypeDefinitionNode {
        return new InputObjectTypeDefinitionNode([
            'name'       => new NameNode(['value' => $directiveArguments->inputTypeName]),
            'fields'     => new NodeList($this->getTranslatableFields($typeDefinition, true)),
            'directives' => new NodeList([]),
        ]);
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

    /**
     * Creates a field definition node for:
     * translations: [<BaseType>Translation!]!
     *
     * @param DirectiveArguments $directiveArguments
     * @return FieldDefinitionNode
     */
    protected function makeTranslationsField(
        DirectiveArguments $directiveArguments,
    ): FieldDefinitionNode {
        $template = $this->viewFactory
            ->make('lighthouse-translatable::translations-field', [
                'attributeName'       => $directiveArguments->translationsAttributeName,
                'translationTypeName' => $directiveArguments->translationTypeName,
            ])
            ->render();

        return Parser::fieldDefinition($template);
    }

    /**
     * Creates an input definition for:
     * translations: [<BaseType>TranslationInput!]!
     *
     * @param DirectiveArguments $directiveArguments
     * @return InputValueDefinitionNode
     */
    protected function makeTranslationsInput(
        DirectiveArguments $directiveArguments,
    ): InputValueDefinitionNode {
        $template = $this->viewFactory
            ->make('lighthouse-translatable::translations-input-field', [
                'attributeName'            => $directiveArguments->translationsAttributeName,
                'translationInputTypeName' => $directiveArguments->inputTypeName,
            ])
            ->render();

        return Parser::inputValueDefinition($template);
    }

    protected function getAttributeFromFieldDefinitionNode(
        FieldDefinitionNode $fieldDefinitionNode,
    ): Attribute {
        $printed = Printer::doPrint($fieldDefinitionNode);

        return $this->fieldDefinitionStringParser->parse($printed);
    }

    protected function makeFieldOrInputDefinition(
        Attribute $parts,
        bool $asInput = false,
    ): FieldDefinitionNode | InputValueDefinitionNode {
        if ($asInput) {
            return Parser::inputValueDefinition(
                $this->viewFactory
                    ->make('lighthouse-translatable::translatable-attribute-input', get_object_vars($parts))
                    ->render()
            );
        }

        return Parser::fieldDefinition(
            $this->viewFactory
                ->make('lighthouse-translatable::translatable-attribute-field', get_object_vars($parts))
                ->render()
        );
    }

    protected function stripTranslatableFieldsFromOriginalTypeDefinition(
        ObjectTypeDefinitionNode $typeDefinition,
    ): ObjectTypeDefinitionNode {
        $this->iterateTranslatableFields($typeDefinition, function ($field, $key) use ($typeDefinition) {
            unset($typeDefinition->fields[$key]);
        });

        return $typeDefinition;
    }

    protected function iterateTranslatableFields(
        ObjectTypeDefinitionNode $typeDefinition,
        callable $action,
    ): void {
        foreach ($typeDefinition->fields as $key => $field) {
            if (ASTHelper::getUnderlyingTypeName($field->type) !== (new TranslatableString)->name) {
                continue;
            }

            $action($field, $key);
        }
    }

    /**
     * @return array<int, FieldDefinitionNode>
     */
    protected function getTranslatableFields(
        ObjectTypeDefinitionNode $typeDefinition,
        bool $asInput = false,
    ): array {
        $fields = [];

        $this->iterateTranslatableFields($typeDefinition, function (FieldDefinitionNode $field) use (&$fields, $asInput) {
            $attribute = $this->getAttributeFromFieldDefinitionNode($field);

            $fields[] = $this->makeFieldOrInputDefinition($attribute, $asInput);
        });

        // Append locale fields
        $fields[] = $this->makeFieldOrInputDefinition(
            new Attribute(
                'locale',
                'String',
                true,
            ),
            $asInput
        );

        return $fields;
    }

    protected function getDirectiveArguments(
        string $rootTypeName,
    ): DirectiveArguments {
        return new DirectiveArguments(
            str_replace('<BaseType>', $rootTypeName, $this->getDirectiveArgumentOrConfigValue('translationTypeName')),
            str_replace('<BaseType>', $rootTypeName, $this->getDirectiveArgumentOrConfigValue('inputTypeName')),
            $this->getDirectiveArgumentOrConfigValue('translationsAttribute'),
            $this->getDirectiveArgumentOrConfigValue('generateTranslationType'),
            $this->getDirectiveArgumentOrConfigValue('generateInputType'),
            $this->directiveArgValue('appendInput', []),
        );
    }

    protected function getDirectiveArgumentOrConfigValue(
        string $argument,
    ): mixed {
        return $this->directiveArgValue(
            $argument,
            $this->config->get('lighthouse-translatable.directive-defaults.' . Str::kebab($argument)),
        );
    }

    protected function validatedTypeDefinition(TypeDefinitionNode $typeDefinition): ObjectTypeDefinitionNode
    {
        if (! $typeDefinition instanceof ObjectTypeDefinitionNode) {
            throw new InvalidArgumentException('Can not apply translatable directive to node of type ' . get_class($typeDefinition));
        }

        return $typeDefinition;
    }
}
