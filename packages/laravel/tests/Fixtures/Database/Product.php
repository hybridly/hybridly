<?php

namespace Hybridly\Tests\Fixtures\Database;

use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'vendor' => Vendor::class,
    ];
}
