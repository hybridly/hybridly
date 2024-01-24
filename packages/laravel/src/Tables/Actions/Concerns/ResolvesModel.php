<?php

namespace Hybridly\Tables\Actions\Concerns;

use Hybridly\Tables\Actions\DataTransferObjects\InlineActionData;
use Illuminate\Database\Eloquent\Model;

trait ResolvesModel
{
    protected \Closure $resolveModelUsing;

    /**
     * Defines how the model should be resolved. The first argument given to the callback is the model class, and the second the action data.
     */
    public function resolveModelUsing(\Closure $resolver): static
    {
        $this->resolveModelUsing = $resolver;

        return $this;
    }

    public function resolveModel(string $modelClass, InlineActionData $data): Model
    {
        $resolver = $this->resolveModelUsing ?? fn (string $modelClass, InlineActionData $data) => $modelClass::findOrFail($data->recordId);

        return \call_user_func($resolver, $modelClass, $data);
    }
}
