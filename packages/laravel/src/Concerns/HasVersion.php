<?php

namespace Hybridly\Concerns;

use Closure;
use Illuminate\Support\Facades\App;

trait HasVersion
{
    private ?Closure $resolveVersionUsing = null;

    /**
     * Gets the asset version for this request.
     */
    public ?string $version {
        get {
            if (! $this->resolveVersionUsing) {
                return null;
            }

            return App::call($this->resolveVersionUsing);
        }
    }

    /**
     * Sets the asset version for the next response.
     */
    public function resolveVersionUsing(Closure $version): static
    {
        $this->resolveVersionUsing = $version;

        return $this;
    }
}
