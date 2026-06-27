<?php

namespace Tests\Feature;

use Hybridly\Support\Header;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OptimisticResponsesTest extends TestCase
{
    #[Test]
    public function the_optimistic_responses_page_returns_a_successful_response(): void
    {
        $response = $this->get('/kitchen-sink/data-loading/optimistic');

        $response->assertStatus(200);
    }

    #[Test]
    public function characters_can_be_liked_and_unliked(): void
    {
        $this
            ->withHeader(Header::HYBRID_REQUEST, 'true')
            ->post('/kitchen-sink/data-loading/optimistic/like', [
                'id' => 1,
                'liked' => true,
            ])
            ->assertHybridProperty('characters.0.liked', true)
            ->assertHybridProperty('characters.0.likes', 313);

        $this
            ->withHeader(Header::HYBRID_REQUEST, 'true')
            ->post('/kitchen-sink/data-loading/optimistic/like', [
                'id' => 1,
                'liked' => false,
            ])
            ->assertHybridProperty('characters.0.liked', false)
            ->assertHybridProperty('characters.0.likes', 312);
    }
}
