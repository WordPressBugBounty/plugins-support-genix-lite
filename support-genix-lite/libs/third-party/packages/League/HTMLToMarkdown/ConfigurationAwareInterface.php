<?php

declare(strict_types=1);

namespace ApbdWps\Vendor\League\HTMLToMarkdown;

interface ConfigurationAwareInterface
{
    public function setConfig(Configuration $config): void;
}
