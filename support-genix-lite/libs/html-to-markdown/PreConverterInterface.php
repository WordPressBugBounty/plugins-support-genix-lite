<?php

declare(strict_types=1);

namespace ApbdWps\HTMLToMarkdown;

interface PreConverterInterface
{
    public function preConvert(ElementInterface $element): void;
}
