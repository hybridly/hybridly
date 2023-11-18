<?php

    declare(strict_types=1);

    namespace Hybridly\Refining\Groups;

    use Hybridly\Components\Concerns\Configurable;
    use Hybridly\Refining\Contracts\Group;
    use Hybridly\Refining\Contracts\Refiner;
    use Hybridly\Refining\Exceptions\InvalidRefinerGroupException;
    use Hybridly\Refining\Refine;
    use Illuminate\Contracts\Database\Eloquent\Builder;

    /**
     * Group base for use.
     *
     * @extends Hybridly\Refining\Contracts\Group
     * @package Hybridly\Refining\Groups
     */
    abstract class BaseGroup implements Group
    {
        use Configurable;

        /**
         * Constructor.
         *
         * @param Refiner[] $refiners Refiners.
         * @param array     $options  Options.
         */
        public function __construct(protected array $refiners = [], protected array $options = [],)
        {
            $this->configure();
        }

        /**
         * Applies the specified refiners with the specified options.
         *
         * @param array $refiners Refiners.
         * @param array $options  Options.
         *
         * @return static
         */
        public static function make(array $refiners = [], array $options = []): static
        {
            return resolve(static::class, compact('refiners', 'options'));
        }

        /**
         * @inheritDoc
         */
        public function options(array $options): static
        {
            $this->options = array_merge($this->options, $options);

            return $this;
        }

        /**
         * @inheritDoc
         */
        public function refiners(array $refiners): static
        {
            $this->refiners = $refiners;

            return $this;
        }

        /**
         * @inheritDoc
         */
        public function getRefiners(): array
        {
            return $this->refiners;
        }

        /**
         * @inheritDoc
         */
        abstract public function refine(Refine $refine, Builder $builder): void;

        /**
         * Check if the refiners applied to the group belong to the same type as the group.
         *
         * @return void
         * @throws InvalidRefinerGroupException
         */
        abstract protected function checkRefinersType(): void;
    }
