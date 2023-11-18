<?php

    declare(strict_types=1);

    namespace Hybridly\Components\Contracts;

    use Closure;

    /**
     * Interface for refiners hideable.
     *
     * @package Hybridly\Components\Contracts
     */
    interface Hideable
    {
        /**
         * Fluent setter the refiner as hidden.
         *
         * @param bool|Closure $condition Is hidden?
         *
         * @return static
         */
        public function hidden(bool | Closure $condition = true): static;

        /**
         * Check if refiner is hidden.
         *
         * @return bool Is hidden?
         */
        public function isHidden(): bool;

        /**
         * @param bool|Closure $condition Is visible?
         *
         * @return static
         */
        public function visible(bool | Closure $condition = true): static;
    }
