<?php

namespace Hybridly\Components\Concerns;

trait Configurable
{
    protected static array $configurations = [];

    public static function configureUsing(\Closure $callback)
    {
        static::$configurations[static::class] ??= [];
        static::$configurations[static::class][] = $callback;
    }

    public function configure(): static
    {
        $this->setUp();

        foreach (static::$configurations as $classToConfigure => $configurationCallbacks) {
            if (!$this instanceof $classToConfigure) {
                continue;
            }

            foreach ($configurationCallbacks as $configure) {
                $configure($this);
            }
        }

        return $this;
    }

    /**
     * Configures this component.
     */
    protected function setUp(): void
    {
    }
}
