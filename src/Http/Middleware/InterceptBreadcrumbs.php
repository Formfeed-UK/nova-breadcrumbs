<?php

namespace Formfeed\Breadcrumbs\Http\Middleware;

use Closure;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

use Laravel\Nova\Nova;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;

use Formfeed\Breadcrumbs\Breadcrumbs;

class InterceptBreadcrumbs {

    public function handle(Request $request, Closure $next) {

        $routeController = $request->route()->getController();

        if ($this->isPageController($routeController) && Nova::breadcrumbsEnabled()) {
            $request = NovaRequest::createFrom($request);
            $response = $next($request);

            if ($response->original instanceof View) {
                $responseData = $response->original->getData();
                $responsePage = $responseData['page'];
            }
            else {
                $responsePage = $response->original;
            }

            $responsePage['props']['breadcrumbs'] = $this->getBreadcrumbs($request, $responsePage['props']['breadcrumbs']);

            return Inertia::render($responsePage['component'], $responsePage['props']);
        } else {
            return $next($request);
        }
    }

    protected function getBreadcrumbs(NovaRequest $request, $breadcrumbs) {
        $breadcrumbs = Breadcrumbs::make(null)->build($request);
        return $breadcrumbs;
    }

    protected function isPageController(Controller $controller) {
        return ((new \ReflectionClass($controller))?->getNamespaceName() ?? false) === "Laravel\Nova\Http\Controllers\Pages";
    }
}
