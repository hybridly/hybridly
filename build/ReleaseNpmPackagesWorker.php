<?php

namespace Build;

use PharIo\Version\Version;
use Symplify\MonorepoBuilder\Release\Contract\ReleaseWorker\ReleaseWorkerInterface;
use Symplify\MonorepoBuilder\Release\Process\ProcessRunner;

final class ReleaseNpmPackagesWorker implements ReleaseWorkerInterface
{
    public function __construct(private ProcessRunner $processRunner)
    {
    }

    public function work(Version $version): void
    {
        $this->processRunner->run($this->getBuildCommand($version));
    }

    public function getDescription(Version $version): string
    {
        return sprintf('Run release script through NPM');
    }

    protected function getBuildCommand(Version $version): string
    {
        // Waiting for https://github.com/antfu/bumpp/pull/3
        dd();

        return sprintf(
            "pnpm bumpp package.json packages/*/package.json --yes --version %s",
            $version->getVersionString(),
        );
    }
}
