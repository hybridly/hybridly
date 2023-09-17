<?php

namespace Hybridly\Tables\Actions;

use Hybridly\Tables\Actions\Concerns\CanDeselectAfterExecuted;

class BulkAction extends BaseAction
{
    use CanDeselectAfterExecuted;
}
