<?php

    declare(strict_types=1);

    namespace Hybridly\Refining;

    use Hybridly\Refining\Contracts\Filter;
    use Hybridly\Refining\Exceptions\InvalidRefinerGroupException;
    use Hybridly\Refining\Groups\BaseGroup;
    use Illuminate\Contracts\Database\Eloquent\Builder;

    /**
     * Group of filters.
     *
     * @extends Hybridly\Refining\Groups\BaseGroup
     * @package Hybridly\Refining
     */
    class FilterGroup extends BaseGroup
    {
        /**
         * Fluent setter the query boolean mode for the refiners in this group.
         *
         * @param string $boolean Query boolean mode.
         *
         * @return static
         */
        public function booleanMode(string $boolean = 'and'): static
        {
            $this->options['boolean'] = $boolean;

            return $this;
        }

        /**
         * @inheritDoc
         */
        public function refine(Refine $refine, Builder $builder): void
        {
            $refine::setGroup($this->refiners, $this->options);

            $this->checkRefinersType();

            $builder->where(function (Builder $group) use ($refine): void {
                foreach ($this->refiners as $refiner) {
                    $refiner->refine($refine, $group);
                }
            });

            $refine::clearGroup();
        }

        /**
         * @inheritDoc
         */
        protected function checkRefinersType(): void
        {
            foreach ($this->refiners as $refiner) {
                if (!$refiner instanceof Filter) {
                    throw InvalidRefinerGroupException::with($refiner::class, Filter::class);
                }
            }
        }
    }
