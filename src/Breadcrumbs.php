<?php

namespace Formfeed\Breadcrumbs;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\Breadcrumbs as NovaBreadcrumbs;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Nova;
use Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Support\Str;

use Formfeed\Breadcrumbs\Concerns\InteractsWithParentResources;
use Formfeed\Breadcrumbs\Concerns\InteractsWithRelationships;


class Breadcrumbs extends NovaBreadcrumbs {

    use InteractsWithParentResources;
    use InteractsWithRelationships;

    protected $request;
    protected $resource;

    protected static $detailBreadcrumbCallback;
    protected static $indexBreadcrumbCallback;
    protected static $formBreadcrumbCallback;
    protected static $resourceBreadcrumbCallback;
    protected static $dashboardBreadcrumbCallback;
    protected static $rootBreadcrumbCallback;

    public static function detailCallback(callable $callback) {
        static::$detailBreadcrumbCallback = $callback;
    }

    public static function indexCallback(callable $callback) {
        static::$indexBreadcrumbCallback = $callback;
    }

    public static function formCallback(callable $callback) {
        static::$formBreadcrumbCallback = $callback;
    }

    public static function resourceCallback(callable $callback) {
        static::$resourceBreadcrumbCallback = $callback;
    }

    public static function dashboardCallback(callable $callback) {
        static::$dashboardBreadcrumbCallback = $callback;
    }

    public static function rootCallback(callable $callback) {
        static::$rootBreadcrumbCallback = $callback;
    }

    public function build(NovaRequest $request = null) {

        $this->request = $request ?? app()->make(NovaRequest::class);
        $this->resource = $this->findResource($this->request);
        $this->items = [];
        $this->breadcrumbArray($request);
        return $this;
    }

    protected function breadcrumbArray(NovaRequest $request) {

        if ($this->pageType($request) === "dashboard") {
            $this->items = $this->dashboardArray($request);
            return;
        }

        if (is_null($this->resource)) {
            return;
        }

        $this->getRelationshipTree($this->resource);

        if (!$this->resource->model()->exists && ($this->request->viaResource && $this->request->viaResourceId)) {
            if ($parent = $this->request->findParentResource()) {
                $this->getRelationshipTree($parent);
            }
        }

        $this->items[] = $this->rootBreadcrumb($request);

        $this->items = array_reverse($this->items);

    }

    protected function dashboardArray(NovaRequest $request) {

        return [
            Breadcrumb::make(__("Home"), config('nova.path', "/nova")),
            Breadcrumb::make(Nova::dashboardForKey($request->route("name"), $request)?->label() ?? __("Dashboard") ),
        ];
    }

    protected function getRelationshipTree($resource) {

        if (is_null($resource)) {
            return;
        }

        $breadcrumbsArray = [];

        $novaClass = $resource::class;

        if ($resource === $this->resource) $breadcrumbsArray[] = $this->formBreadcrumb($this->request, $resource);
        $breadcrumbsArray[] = $this->detailBreadcrumb($this->request, $resource);

        if ($resource->model()->exists) {
            $breadcrumbsArray[] = $this->indexBreadcrumb($this->request, $resource);
        }

        $breadcrumbsArray = $this->resourceBreadcrumbs($this->request, $resource, $breadcrumbsArray);

        if (!is_array($breadcrumbsArray)) {
            $breadcrumbsArray = [$breadcrumbsArray];
        }

        $this->items = array_merge($this->items, $breadcrumbsArray);

        if (!property_exists($novaClass, "resolveParentBreadcrumbs") || $novaClass::$resolveParentBreadcrumbs !== false) {
            $resource = $this->getParentResource($resource);
            $this->getRelationshipTree($resource);
        };
    }

    protected function indexBreadcrumb(NovaRequest $request, $resource) {

        if (method_exists($resource, "indexBreadcrumb")) {
            return $resource->indexBreadcrumb($request, $this, Breadcrumb::indexResource($resource));
        }

        if (!is_null(static::$indexBreadcrumbCallback)) {
            return call_user_func(static::$indexBreadcrumbCallback, [$request, $this, Breadcrumb::indexResource($resource)]);
        }

        return Breadcrumb::indexResource($resource);
    }

    protected function detailBreadcrumb(NovaRequest $request, $resource) {

        if (method_exists($resource, "detailBreadcrumb")) {
            return call_user_func($resource->detailBreadcrumb, [$request, $this, Breadcrumb::resource($resource)]);
        }

        if (!is_null(static::$detailBreadcrumbCallback)) {
            return call_user_func(static::$detailBreadcrumbCallback, [$request, $this, Breadcrumb::resource($resource)]);
        }

        return Breadcrumb::resource($resource);
    }

    protected function formBreadcrumb($request, $resource) {
        $type = $this->pageType($request);

        if (method_exists($resource, "formBreadcrumb")) {
            return $resource->formBreadcrumb($request, $this, Breadcrumb::resource($resource), $type);
        }

        if (!is_null(static::$formBreadcrumbCallback)) {
            return call_user_func(static::$formBreadcrumbCallback, [$request, $this, Breadcrumb::resource($resource), $type]);
        }

        if (!is_null($type) && !in_array($type, ["index", "detail", "dashboard"])) {
            return Breadcrumb::make(__(Str::ucfirst($type)), null);
        }
    }

    protected function dashboardBreadcrumb(NovaRequest $request, $resource) {

        $dashboard = Nova::dashboardForKey($request->route("name"), $request);

        if (is_null($dashboard)) {
            return;
        }

        if (method_exists($dashboard, "dashboardBreadcrumb")) {
            return $dashboard->dashboardBreadcrumb($request, $this, Breadcrumb::make($dashboard?->label() ?? __("Dashboard")));
        }

        if (!is_null(static::$dashboardBreadcrumbCallback)) {
            return call_user_func(static::$dashboardBreadcrumbCallback, [$request, $this, Breadcrumb::make($dashboard?->label() ?? __("Dashboard"))]);
        }

        return Breadcrumb::make($dashboard?->label() ?? __("Dashboard"));
    }

    protected function resourceBreadcrumbs(NovaRequest $request, $resource, $breadcrumbArray) {

        if (method_exists($resource, "breadcrumbs")) {
            return $resource->breadcrumbs($request, $this, $breadcrumbArray);
        }

        if (!is_null(static::$resourceBreadcrumbCallback)) {
            return call_user_func(static::$resourceBreadcrumbCallback, [$request, $this, $breadcrumbArray]);
        }

        return $breadcrumbArray;
    }

    protected function rootBreadcrumb(NovaRequest $request) {
        if (!is_null(static::$rootBreadcrumbCallback)) {
            return call_user_func(static::$rootBreadcrumbCallback, [$request, $this, Breadcrumb::make(__("Home"), config('nova.path', "/nova"))]);
        }

        return Breadcrumb::make(__("Home"), config('nova.path', "/nova"));
    }

    public function findResource(NovaRequest $request) {
        return rescue(function () use ($request) {
            return $request->newResourceWith($this->findModel($request));
        }, null, false);
    }

    public function findModel(NovaRequest $request) {
        $resourceId = $request->resourceId ?? null;
        return rescue(function () use ($request, $resourceId) {
            return $request->findModel($resourceId);
        }, null, false);
    }

    protected function pageType(NovaRequest $request) {
        $controller = $request->route()->getController();
        switch ($controller::class) {
            case Pages\ResourceDetailController::class:
                return "detail";
            case Pages\ResourceIndexController::class:
                return "index";
            case Pages\ResourceCreateController::class:
                return "create";
            case Pages\ResourceUpdateController::class:
                return "update";
            case Pages\ResourceReplicateController::class:
                return "replicate";
            case Pages\AttachableController::class:
            case Pages\AttachedResourceUpdateController::class:
                return "attach";
            case Pages\DashboardController::class:
                return "dashboard";
            default:
                return null;
        }
    }

    public function jsonSerialize(): array {
        return $this->authorizedToSee(app(NovaRequest::class))
            ? array_values(array_filter($this->items))
            : [];
    }
}
