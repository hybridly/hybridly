<?php

namespace Hybridly\Refining;

use Hybridly\Components;
use Hybridly\Refining\Contracts\Refiner;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Group implements Refiner
{
    use Components\Concerns\Configurable;
    use Components\Concerns\EvaluatesClosures;
    use Components\Concerns\IsHideable;

    public function __construct(
        protected array $refiners = [],
        protected array $options = [],
    ) {
        $this->configure();
    }

    /**
     * Merges the specified options for this group.
     */
    public function options(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Defines the refiners for this group.
     */
    public function refiners(array $refiners): static
    {
        $this->refiners = $refiners;

        return $this;
    }

    /**
     * Gets the refiners in this group.
     */
    public function getRefiners(): array
    {
        return $this->refiners;
    }

    /**
     * Defines the query boolean mode for the refiners in this group.
     */
    public function booleanMode(string $boolean = 'and'): static
    {
        $this->options['boolean'] = $boolean;

        return $this;
    }

    /** @internal */
    public function refine(Refine $refine, Builder $builder): void
    {
        $refine::setGroup($this->refiners, $this->options);

        $builder->where(function (Builder $group) use ($refine) {
            foreach ($this->refiners as $refiner) {
                /** @var Refiner $refiner */
                $refiner->refine($refine, $group);
            }
        });

        $refine::clearGroup();
    }

    public function isActive(): bool
    {
        return true;
    }

    /**
     * Applies the specified refiners with the specified options.
     */
    public static function make(array $refiners = [], array $options = []): static
    {
        return resolve(static::class, [
            'refiners' => $refiners,
            'options' => $options,
        ]);
    }
}
