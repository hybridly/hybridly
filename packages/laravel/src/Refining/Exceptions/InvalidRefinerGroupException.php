<?php

    declare(strict_types=1);

    namespace Hybridly\Refining\Exceptions;

    use Exception;

    final class InvalidRefinerGroupException extends Exception
    {
        public static function with(string $refiner, string $group): self
        {
            return new self("Refiners of the [$refiner] type cannot be used in a refiner Group of the [$group] type.");
        }
    }
