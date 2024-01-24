<?php

namespace Hybridly\Tables\Actions;

use Closure;
use Hybridly\Tables\Actions\DataTransferObjects\InlineActionData;
use Illuminate\Database\Eloquent\Model;

class InlineAction extends BaseAction
{
    protected Closure $modelResolver;

    public function resolveModelUsing(Closure $resolver): static
    {
        $this->modelResolver = $resolver;

        return $this;
    }

    public function resolveModel(string $modelClass, InlineActionData $data): Model
    {
        $resolver = $this->modelResolver ?? fn (string $modelClass, InlineActionData $data) => $modelClass::findOrFail($data->recordId);

        return \call_user_func($resolver, $modelClass, $data);
    }
}
