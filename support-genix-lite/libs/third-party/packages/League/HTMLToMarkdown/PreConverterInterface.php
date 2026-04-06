<?php

declare(strict_types=1);

namespace ApbdWps\Vendor\League\HTMLToMarkdown;

interface PreConverterInterface
{
    public function preConvert(ElementInterface $element): void;
}
