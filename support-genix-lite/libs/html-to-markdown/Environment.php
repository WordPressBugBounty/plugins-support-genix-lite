<?php

declare(strict_types=1);

namespace ApbdWps\HTMLToMarkdown;

use ApbdWps\HTMLToMarkdown\Converter\BlockquoteConverter;
use ApbdWps\HTMLToMarkdown\Converter\CodeConverter;
use ApbdWps\HTMLToMarkdown\Converter\CommentConverter;
use ApbdWps\HTMLToMarkdown\Converter\ConverterInterface;
use ApbdWps\HTMLToMarkdown\Converter\DefaultConverter;
use ApbdWps\HTMLToMarkdown\Converter\DivConverter;
use ApbdWps\HTMLToMarkdown\Converter\EmphasisConverter;
use ApbdWps\HTMLToMarkdown\Converter\HardBreakConverter;
use ApbdWps\HTMLToMarkdown\Converter\HeaderConverter;
use ApbdWps\HTMLToMarkdown\Converter\HorizontalRuleConverter;
use ApbdWps\HTMLToMarkdown\Converter\ImageConverter;
use ApbdWps\HTMLToMarkdown\Converter\LinkConverter;
use ApbdWps\HTMLToMarkdown\Converter\ListBlockConverter;
use ApbdWps\HTMLToMarkdown\Converter\ListItemConverter;
use ApbdWps\HTMLToMarkdown\Converter\ParagraphConverter;
use ApbdWps\HTMLToMarkdown\Converter\PreformattedConverter;
use ApbdWps\HTMLToMarkdown\Converter\TextConverter;

final class Environment
{
    /** @var Configuration */
    protected $config;

    /** @var ConverterInterface[] */
    protected $converters = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = new Configuration($config);
        $this->addConverter(new DefaultConverter());
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function addConverter(ConverterInterface $converter): void
    {
        if ($converter instanceof ConfigurationAwareInterface) {
            $converter->setConfig($this->config);
        }

        foreach ($converter->getSupportedTags() as $tag) {
            $this->converters[$tag] = $converter;
        }
    }

    public function getConverterByTag(string $tag): ConverterInterface
    {
        if (isset($this->converters[$tag])) {
            return $this->converters[$tag];
        }

        return $this->converters[DefaultConverter::DEFAULT_CONVERTER];
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createDefaultEnvironment(array $config = []): Environment
    {
        $environment = new static($config);

        $environment->addConverter(new BlockquoteConverter());
        $environment->addConverter(new CodeConverter());
        $environment->addConverter(new CommentConverter());
        $environment->addConverter(new DivConverter());
        $environment->addConverter(new EmphasisConverter());
        $environment->addConverter(new HardBreakConverter());
        $environment->addConverter(new HeaderConverter());
        $environment->addConverter(new HorizontalRuleConverter());
        $environment->addConverter(new ImageConverter());
        $environment->addConverter(new LinkConverter());
        $environment->addConverter(new ListBlockConverter());
        $environment->addConverter(new ListItemConverter());
        $environment->addConverter(new ParagraphConverter());
        $environment->addConverter(new PreformattedConverter());
        $environment->addConverter(new TextConverter());

        return $environment;
    }
}
