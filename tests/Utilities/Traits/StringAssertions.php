<?php

declare(strict_types=1);

namespace Tests\Utilities\Traits;

use PHPUnit\Framework\ExpectationFailedException;

trait StringAssertions
{
    static protected function assertStringNotContainsSubstringIgnoringLeadingWhitespace(
        string $needle,
        string $haystack,
        string $message = '',
    ): void {
        static::assertStringNotContainsString(
            static::trimWhitespaceOnEveryLine($needle),
            static::trimWhitespaceOnEveryLine($haystack),
            $message,
        );
    }

    protected static function assertStringContainsSubstringIgnoringLeadingWhitespace(
        string $needle,
        string $haystack,
        string $message = '',
    ): void {
        static::assertStringContainsString(
            static::trimWhitespaceOnEveryLine($needle),
            static::trimWhitespaceOnEveryLine($haystack),
            $message,
        );
    }

    protected static function trimWhitespaceOnEveryLine(string $string): string
    {
        return implode(
            PHP_EOL,
            array_map(
                fn (string $line) => trim($line, ' '),
                explode(PHP_EOL, $string)
            ),
        );
    }

    /**
     * @throws ExpectationFailedException
     */
    abstract public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void;

    /**
     * @throws ExpectationFailedException
     */
    abstract public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void;
}
