<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoUnescapedMarkupRule;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

final class SafeBuilder
{

    use StringTranslationTrait;

    public function literal(): array
    {
        return ['#markup' => '<p>Static copy.</p>'];
    }

    public function concatenatedLiterals(): array
    {
        return ['#markup' => '<p>' . 'Static copy.' . '</p>'];
    }

    public function translated(): array
    {
        // $this->t() returns TranslatableMarkup, which is already escaped.
        return ['#markup' => $this->t('Static copy.')];
    }

    public function proceduralTranslated(): array
    {
        return ['#markup' => t('Static copy.')];
    }

    public function explicitlyFiltered(string $bio): array
    {
        return ['#markup' => Xss::filter($bio)];
    }

    public function explicitlyEscaped(string $bio): array
    {
        return ['#markup' => Html::escape($bio)];
    }

    public function explicitMarkup(string $bio): array
    {
        return ['#markup' => Markup::create(Xss::filter($bio))];
    }

    public function checkedMarkup(string $body, string $format): array
    {
        return ['#markup' => check_markup($body, $format)];
    }

    public function withAllowedTags(string $bio): array
    {
        // The author has thought about the tag whitelist; leave it alone.
        return [
            '#markup' => $bio,
            '#allowed_tags' => ['em', 'strong'],
        ];
    }

    public function plainText(string $bio): array
    {
        // The recommended fix: #plain_text is escaped wholesale.
        return ['#plain_text' => $bio];
    }

    public function dynamicKey(string $key, string $bio): array
    {
        // A computed key cannot be matched, and guessing would be worse than
        // staying quiet.
        return [$key => $bio];
    }
}
