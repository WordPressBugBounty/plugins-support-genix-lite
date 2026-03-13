<?php

declare(strict_types=1);

namespace ApbdWps\HTMLToMarkdown;

interface ConfigurationAwareInterface
{
    public function setConfig(Configuration $config): void;
}
