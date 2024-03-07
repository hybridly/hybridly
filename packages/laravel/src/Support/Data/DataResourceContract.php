<?php

namespace Hybridly\Support\Data;

use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Contracts\TransformableData;

interface DataResourceContract extends BaseData, TransformableData
{
    /**
     * Gets the authorizations to be resolved for this data class.
     */
    public static function getAuthorizations(): array;
}
