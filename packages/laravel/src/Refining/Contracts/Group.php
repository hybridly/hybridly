<?php

    declare(strict_types=1);

    namespace Hybridly\Refining\Contracts;

    use Hybridly\Refining\Exceptions\InvalidRefinerGroupException;
    use Hybridly\Refining\Refine;
    use Illuminate\Contracts\Database\Eloquent\Builder;

    /**
     * Contract for group of refiners.
     *
     * @package Hybridly\Refining\Contracts
     */
    interface Group
    {
        /**
         * Gets the refiners in this group.
         *
         * @return Refiner[] Refiners.
         */
        public function getRefiners(): array;

        /**
         * Fluent merger the specified options for this group.
         *
         * @param array $options Options.
         *
         * @return static
         */
        public function options(array $options): static;

        /**
         * Performs the refining process.
         *
         * @param Refine  $refine  Refine.
         * @param Builder $builder Query builder.
         *
         * @return void
         * @throws InvalidRefinerGroupException
         */
        public function refine(Refine $refine, Builder $builder): void;

        /**
         * Fluent setter the refiners for this group.
         *
         * @param Refiner[] $refiners Refiners.
         *
         * @return static
         */
        public function refiners(array $refiners): static;
    }
