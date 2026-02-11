<?php

namespace Hybridly\Tables\Actions\Concerns;

use Closure;

trait HasUrl
{
    protected null|array|string|\Closure $url = null;
    protected mixed $parameters = [];

    public function url(null|string|array|\Closure $url, mixed $parameters = []): static
    {
        $this->url = $url;
        $this->parameters = $parameters;

        return $this;
    }

    public function getUrl(): ?string
    {
        if ($this->url instanceof Closure) {
            return $this->evaluate($this->url);
        }

        $url = $this->evaluate($this->url);

        if (is_array($url)) {
            return action($url, $this->parameters);
        }

        if (is_string($url) && class_exists($url) && method_exists($url, '__invoke')) {
            return action($url, $this->parameters);
        }

        return $url;
    }
}
