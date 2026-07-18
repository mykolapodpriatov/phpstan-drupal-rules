<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoGlobalTFunctionInClassRule;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A class that translates strings the DI-friendly way, via $this->t().
 */
final class InjectedTController
{

    use StringTranslationTrait;

    public function build(): array
    {
        // Good: $this->t() is a MethodCall, not the global FuncCall.
        $title = $this->t('Welcome');

        return [
            '#title' => $title,
        ];
    }

}

/**
 * Procedural glue outside any class may keep using the global t().
 */
function global_t_helper(): string
{
    // Good: no class scope here, so the global t() is acceptable.
    return t('Welcome');
}
