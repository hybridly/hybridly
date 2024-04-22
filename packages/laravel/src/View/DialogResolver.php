<?php

namespace Hybridly\View;

use Hybridly\Hybridly;
use Hybridly\Support\Header;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;

final class DialogResolver
{
    public function __construct(
        private readonly Hybridly $hybridly,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function resolve(View $view, string $baseUrl, Request $request): ?Dialog
    {
        return new Dialog(
            component: $view->component,
            properties: $view->properties,
            baseUrl: $baseUrl,
            redirectUrl: $this->getRedirectUrl($request) ?? $baseUrl,
            key: $request->header(Header::DIALOG_KEY, str()->uuid()->toString()),
        );
    }

    protected function getRedirectUrl(Request $request): ?string
    {
        if ($redirect = $request->headers->get(Header::DIALOG_REDIRECT)) {
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
