<?php
// phpcs:ignoreFile -- Bundled third-party library (Composer + Mozart); not subject to plugin coding standards.

declare(strict_types=1);

namespace ApbdWps\Vendor\League\HTMLToMarkdown\Converter;

use ApbdWps\Vendor\League\HTMLToMarkdown\ElementInterface;

interface ConverterInterface
{
    public function convert(ElementInterface $element): string;

    /**
     * @return string[]
     */
    public function getSupportedTags(): array;
}
