<?php

namespace Hybridly\Testing;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;

class TestFileViewFinder extends FileViewFinder
{
    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        parent::__construct($files, $paths, $extensions);
    }

    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        /**
         * If a domain is detected from the view name, we will add the domains
         * directory to the list of paths as well as transform the view name
         * ex: domain:test -> domains.domain.pages.test
         */
        if (str_contains($name, ':')) {
            $this->paths[] = resource_path('domains');
            [$domain, $page] = explode(':', $name);
            $name = "{$domain}.pages.{$page}";
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }
}
