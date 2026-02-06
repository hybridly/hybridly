<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasPreviewLabel
{
    protected null|string|\Closure $previewLabel = null;

    public function previewLabel(string|\Closure $previewLabel): static
    {
        $this->previewLabel = $previewLabel;

        return $this;
    }

    public function getPreviewLabel(): ?string
    {
        return $this->evaluate($this->previewLabel);
    }
}
