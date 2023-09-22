<?php

namespace Hybridly\Tables\Columns;

class TextColumn extends BaseColumn
{
    protected function setUp(): void
    {
        $this->type('text');
    }
}
