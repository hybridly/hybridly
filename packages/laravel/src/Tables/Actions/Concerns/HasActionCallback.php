<?php

namespace Hybridly\Tables\Actions\Concerns;

trait HasActionCallback
{
    protected \Closure|null $actionCallback = null;

    public function action(\Closure|string|null $callback): static
    {
        if (\is_string($callback) && class_exists($callback) && method_exists($callback, '__invoke')) {
            $callback = resolve($callback)->__invoke(...);
        }

        $this->actionCallback = $callback;

        return $this;
    }

    public function getAction(): ?\Closure
    {
        return $this->actionCallback;
    }
}
