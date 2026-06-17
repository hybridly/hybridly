<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MergeableTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mergeable_table_page_returns_a_successful_response(): void
    {
        $response = $this->get('/kitchen-sink/tables/mergeable');

        $response->assertStatus(200);
    }
}
