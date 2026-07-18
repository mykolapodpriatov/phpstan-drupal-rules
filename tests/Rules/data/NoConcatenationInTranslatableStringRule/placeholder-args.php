<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoConcatenationInTranslatableStringRule;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A class that keeps a single source string and passes values as placeholders.
 */
final class PlaceholderMessages
{

    use StringTranslationTrait;

    public function greet(string $name): string
    {
        // Good: static source string, dynamic value passed as a placeholder.
        return (string) $this->t('Hello @name', ['@name' => $name]);
    }

    public function farewell(string $name): string
    {
        // Good: same for the global t() in procedural-style code.
        return t('Goodbye @name', ['@name' => $name]);
    }

}
