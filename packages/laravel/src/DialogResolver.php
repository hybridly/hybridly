<?php

namespace Hybridly;

use Hybridly\View\Dialog;
use Hybridly\View\View;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;

class DialogResolver
{
    public function __construct(
        private Hybridly $hybridly,
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function resolve(View $view, string $baseUrl, Request $request): ?Dialog
    {
        return new Dialog(
            component: $view->component,
            properties: $view->properties,
            baseUrl: $baseUrl,
            redirectUrl: $this->getRedirectUrl($request) ?? $baseUrl,
        );
    }

    protected function getRedirectUrl(Request $request): ?string
    {
        if (!$referer = $request->headers->get('referer')) {
            return null;
        }

        if ($referer === $this->urlGenerator->current()) {
            return null;
        }

        if (!$this->hybridly->isHybrid($request)) {
            return null;
        }

        return $referer;
    }
}
