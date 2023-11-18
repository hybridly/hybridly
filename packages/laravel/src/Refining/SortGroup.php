<?php

    declare(strict_types=1);

    namespace Hybridly\Refining;

    use Hybridly\Refining\Contracts\Sort;
    use Hybridly\Refining\Exceptions\InvalidRefinerGroupException;
    use Hybridly\Refining\Groups\BaseGroup;
    use Illuminate\Contracts\Database\Eloquent\Builder;

    /**
     * Group of sorters.
     *
     * @extends Hybridly\Refining\Groups\BaseGroup
     * @package Hybridly\Refining
     */
    class SortGroup extends BaseGroup
    {
        /**
         * @inheritDoc
         */
        public function refine(Refine $refine, Builder $builder): void
        {
            $refine::setGroup($this->refiners, $this->options);

            $this->checkRefinersType();

            $refine->setMultiSorting();

            foreach ($this->refiners as $refiner) {
                $refiner->refine($refine, $builder);
            }

            $refine::clearGroup();
        }

        /**
         * @inheritDoc
         */
        protected function checkRefinersType(): void
        {
            foreach ($this->refiners as $refiner) {
                if (!$refiner instanceof Sort) {
                    throw InvalidRefinerGroupException::with($refiner::class, Sort::class);
                }
            }
        }
    }
