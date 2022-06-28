<?php

namespace Hybridly;

class LazyProperty
{
    public function __construct(
        protected \Closure $callback,
    ) {
    }

    public function __invoke(): mixed
    {
        return app()->call($this->callback);
    }
}
