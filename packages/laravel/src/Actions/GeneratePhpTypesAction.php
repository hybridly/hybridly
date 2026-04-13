<?php

namespace Hybridly\Actions;

use Psr\Container\ContainerInterface;
use Tempest\Generation\TypeScript\TypeScriptGenerationConfig;
use Tempest\Generation\TypeScript\TypeScriptGenerator;

final class GeneratePhpTypesAction
{
    public const PHP_TYPES_PATH = '.hybridly/php-types.d.ts';

    public function __construct(
        private ContainerInterface $container,
        private TypeScriptGenerator $generator,
        private TypeScriptGenerationConfig $config,
    ) {}

    /**
     * @return array<string, TransformedType>
     */
    public function __invoke(): array
    {
        $output = $this->generator->generate();
        $writer = $this->container->get($this->config->writer);
        $writer->write($output);

        return $output->getAllDefinitions();
    }
}
