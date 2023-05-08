<?php

namespace Hybridly\Tests\Fixtures\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use SoftDeletes;
}
