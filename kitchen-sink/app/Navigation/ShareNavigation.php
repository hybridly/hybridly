<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Http404Controller;
use App\Http500Controller;
use App\KitchenSink\DataLoading\Deferred\DeferredPropertiesController;
use App\KitchenSink\DataLoading\Mergeable\MergeablePropertiesController;
use App\KitchenSink\DataLoading\WhenVisible\WhenVisibleController;
use App\KitchenSink\Forms\FormComponent\FormComponentController;
use App\KitchenSink\Forms\Validation\ValidationController;
use App\KitchenSink\Navigation\AsyncRequests\AsyncRequestsController;
use App\KitchenSink\Navigation\Lifecycle\LifecycleController;
use App\KitchenSink\Navigation\PreserveScroll\PreserveScrollController;
use App\KitchenSink\Navigation\PreserveState\PreserveStateController;
use App\KitchenSink\Navigation\Response\ResponsesController;
use App\KitchenSink\Navigation\ViewTransitions\ViewTransitionsController;
use App\ShowIndexController;
use Closure;
use Discovery\Routing\Middleware;
use Hybridly\Hybridly;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Middleware]
final readonly class ShareNavigation
{
    public function __construct(
        private Hybridly $hybridly,
        private NavigationBuilder $navigation_builder,
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        $this->registerNavigation($this->navigation_builder);

        $this->hybridly->persist(['navigation', 'breadcrumbs']);
        $this->hybridly->share('navigation', $this->navigation_builder->getSidebarItems());
        $this->hybridly->share('breadcrumbs', $this->navigation_builder->getBreadcrumbs());

        return $next($request);
    }

    private function registerNavigation(NavigationBuilder $builder): void
    {
        $builder->addSidebarItem(new NavigationItem(
            label: 'Home',
            icon: 'lucide:home',
            href: action(ShowIndexController::class, absolute: false),
            route_patterns: ['index'],
        ));

        $builder->addSidebarItem(new NavigationItem(
            label: 'Data loading',
            icon: 'lucide:database',
            children: [
                new NavigationItem(
                    label: 'Deferred properties',
                    icon: 'lucide:clock',
                    href: action(DeferredPropertiesController::class, absolute: false),
                    route_patterns: ['kitchen-sink.data-loading.deferred*'],
                ),
                new NavigationItem(
                    label: 'Mergeable properties',
                    icon: 'lucide:git-merge',
                    href: action(MergeablePropertiesController::class, absolute: false),
                    route_patterns: ['kitchen-sink.data-loading.mergeable*'],
                ),
                new NavigationItem(
                    label: 'When visible',
                    icon: 'lucide:eye',
                    href: action(WhenVisibleController::class, absolute: false),
                    route_patterns: ['kitchen-sink.data-loading.when-visible*'],
                ),
            ],
        ));

        $builder->addSidebarItem(new NavigationItem(
            label: 'Navigation',
            icon: 'lucide:compass',
            children: [
                new NavigationItem(
                    label: 'Lifecycle',
                    icon: 'lucide:repeat',
                    href: action(LifecycleController::class, absolute: false),
                    route_patterns: ['kitchen-sink.navigation.lifecycle*'],
                ),
                new NavigationItem(
                    label: 'Responses',
                    icon: 'lucide:reply',
                    href: action(ResponsesController::class, absolute: false),
                    route_patterns: ['kitchen-sink.navigation.response*'],
                ),
                new NavigationItem(
                    label: 'Preserve state',
                    icon: 'lucide:save',
                    href: action(PreserveStateController::class, absolute: false),
                    route_patterns: ['kitchen-sink.navigation.preserve-state*'],
                ),
                new NavigationItem(
                    label: 'Preserve scroll',
                    icon: 'lucide:scroll',
                    href: action(PreserveScrollController::class, absolute: false),
                    route_patterns: ['kitchen-sink.navigation.preserve-scroll*'],
                ),
                new NavigationItem(
                    label: 'Async requests',
                    icon: 'lucide:loader',
                    href: action(AsyncRequestsController::class, absolute: false),
                    route_patterns: ['kitchen-sink.navigation.async-requests*'],
                ),
                new NavigationItem(
                    label: 'View transitions',
                    icon: 'lucide:wand',
                    href: action([ViewTransitionsController::class, 'index'], absolute: false),
                    route_patterns: ['kitchen-sink.navigation.view-transitions*'],
                ),
                new NavigationItem(
                    label: 'HTTP 404',
                    icon: 'tabler:error-404',
                    href: action(Http404Controller::class, absolute: false),
                ),
                new NavigationItem(
                    label: 'HTTP 500',
                    icon: 'lucide:x',
                    href: action(Http500Controller::class, absolute: false),
                ),
            ],
        ));

        $builder->addSidebarItem(new NavigationItem(
            label: 'Forms',
            icon: 'lucide:form-input',
            children: [
                new NavigationItem(
                    label: 'Validation',
                    icon: 'lucide:shield-check',
                    href: action(ValidationController::class, absolute: false),
                    route_patterns: ['kitchen-sink.forms.validation*'],
                ),
                new NavigationItem(
                    label: 'Form component',
                    icon: 'lucide:file-pen-line',
                    href: action(FormComponentController::class, absolute: false),
                    route_patterns: ['kitchen-sink.forms.form-component*'],
                ),
            ],
        ));
    }
}
