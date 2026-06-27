<?php

namespace App\KitchenSink\DataLoading\Optimistic;

use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use function Hybridly\properties;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/data-loading/optimistic', name: 'kitchen-sink.data-loading.optimistic')]
final class OptimisticResponsesController
{
    public function __construct(
        private readonly CharacterRepository $characters,
    ) {}

    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::data-loading.optimistic.index', [
            'characters' => $this->characters->all(),
        ]);
    }

    #[Post('/like', name: 'like')]
    public function setLiked(Request $request): HybridResponse
    {
        if ($request->boolean('fail')) {
            throw ValidationException::withMessages([
                'like' => 'error',
            ]);
        }

        return properties([
            'characters' => $this->characters->setLiked(
                id: $request->integer('id'),
                liked: $request->boolean('liked'),
            ),
        ]);
    }
}
