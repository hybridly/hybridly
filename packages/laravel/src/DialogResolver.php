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
            key: $request->header(Hybridly::DIALOG_KEY_HEADER, str()->uuid()->toString()),
        );
    }

    protected function getRedirectUrl(Request $request): ?string
    {
        if ($redirect = $request->headers->get(Hybridly::DIALOG_REDIRECT_HEADER)) {
            return $redirect;
        }

        if (empty($referer = $request->headers->get('referer'))) {
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
