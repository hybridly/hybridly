<?php

namespace Hybridly\Concerns;

trait ResolvesUrls
{
    /**
     * @return callable(\Illuminate\Http\Request):string|null
     */
    protected ?\Closure $urlResolver = null;

    /**
     * Defines how the URL for the next response is resolved.
     *
     * @param ?callable(\Illuminate\Http\Request):string|null $resolver
     */
    public function setUrlResolver(?\Closure $resolver = null): static
    {
        $this->urlResolver = $resolver;

        return $this;
    }

    /**
     * @return ?callable(\Illuminate\Http\Request):string|null
     */
    public function getUrlResolver(): ?\Closure
    {
        return $this->urlResolver;
    }
}
