<?php

declare(strict_types=1);

namespace ApbdWps\HTMLToMarkdown\Converter;

use ApbdWps\HTMLToMarkdown\ElementInterface;

interface ConverterInterface
{
    public function convert(ElementInterface $element): string;

    /**
     * @return string[]
     */
    public function getSupportedTags(): array;
}
