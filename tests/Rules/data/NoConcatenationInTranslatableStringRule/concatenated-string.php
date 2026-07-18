<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoConcatenationInTranslatableStringRule;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A class that concatenates dynamic values straight into the source string.
 */
final class ConcatMessages
{

    use StringTranslationTrait;

    public function greet(string $name): string
    {
        // Bad: concatenation in the first argument of $this->t().
        return (string) $this->t('Hello ' . $name);
    }

    public function farewell(string $name): string
    {
        // Bad: concatenation in the first argument of the global t().
        return t('Goodbye ' . $name);
    }

}
