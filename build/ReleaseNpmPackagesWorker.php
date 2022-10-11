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
        return sprintf(
            "pnpm bumpp %s package.json packages/*/package.json --yes",
            $version->getVersionString(),
        );
    }
}
