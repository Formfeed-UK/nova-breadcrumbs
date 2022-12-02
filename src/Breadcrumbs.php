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
use Illuminate\Support\Arr;

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

        $this->items[] = $this->rootBreadcrumb($request);

        if ($this->pageType($request) === "dashboard") {
            array_push($this->items, ...$this->dashboardBreadcrumb($request));
            return;
        }

        if (is_null($this->resource)) {
            return;
        }

        if (!$this->resource->model()->exists && ($this->request->viaResource && $this->request->viaResourceId)) {
            if ($parent = $this->request->findParentResource()) {
                $this->getRelationshipTree($parent);
            }
        }

        $this->getRelationshipTree($this->resource);
    }

    protected function getRelationshipTree($resource) {

        if (is_null($resource)) {
            return;
        }

        $breadcrumbsArray = [];

        $novaClass = $resource::class;

        if (!property_exists($novaClass, "resolveParentBreadcrumbs") || $novaClass::$resolveParentBreadcrumbs !== false) {
            $this->getRelationshipTree($this->getParentResource($resource));
        };

        if ($resource->model()->exists) {
            array_push($breadcrumbsArray, ...$this->indexBreadcrumb($this->request, $resource));
            array_push($breadcrumbsArray, ...$this->detailBreadcrumb($this->request, $resource));
        }
        else {
            array_push($breadcrumbsArray, ...$this->indexBreadcrumb($this->request, $resource));
        }

        // Add Form Breadcrumbs
        if ($resource === $this->resource) array_push($breadcrumbsArray, ...$this->formBreadcrumb($this->request, $resource));

        // Modify Array via Resource Callback
        $breadcrumbsArray = $this->resourceBreadcrumbs($this->request, $resource, $breadcrumbsArray);

        $this->items = array_merge($this->items, $breadcrumbsArray);

    }

    protected function indexBreadcrumb(NovaRequest $request, $resource) {

        if (method_exists($resource, "indexBreadcrumb")) {
            return Arr::wrap($resource->indexBreadcrumb($request, $this, Breadcrumb::indexResource($resource)));
        }

        if (!is_null(static::$indexBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$indexBreadcrumbCallback, [$request, $this, Breadcrumb::indexResource($resource)]));
        }

        return Arr::wrap(Breadcrumb::indexResource($resource));
    }

    protected function detailBreadcrumb(NovaRequest $request, $resource) {

        if (method_exists($resource, "detailBreadcrumb")) {
            return Arr::wrap($resource->detailBreadcrumb($request, $this, Breadcrumb::resource($resource)));
        }

        if (!is_null(static::$detailBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$detailBreadcrumbCallback, [$request, $this, Breadcrumb::resource($resource)]));
        }

        return Arr::wrap(Breadcrumb::resource($resource));
    }

    protected function formBreadcrumb($request, $resource) {
        $type = $this->pageType($request);

        if (!is_null($type) && in_array($type, ["index", "detail", "dashboard"])) {
            return [];
        }

        if (method_exists($resource, "formBreadcrumb")) {
            return Arr::wrap($resource->formBreadcrumb($request, $this, Breadcrumb::resource($resource), $type));
        }

        if (!is_null(static::$formBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$formBreadcrumbCallback, [$request, $this, Breadcrumb::resource($resource), $type]));
        }

        if ($type === "attach") {
            $relatedResourceClass = $request->relatedResource();
            return [
                Breadcrumb::make(__("Attach")),
                Breadcrumb::indexResource($relatedResourceClass)
            ];
        }

        return Arr::wrap(Breadcrumb::make(__(Str::ucfirst($type)), null));

    }

    protected function dashboardBreadcrumb(NovaRequest $request) {

        $dashboard = Nova::dashboardForKey($request->route("name"), $request);

        if (is_null($dashboard)) {
            return;
        }

        if (method_exists($dashboard, "dashboardBreadcrumb")) {
            return Arr::wrap($dashboard->dashboardBreadcrumb($request, $this, Breadcrumb::make($dashboard?->label() ?? __("Dashboard"))));
        }

        if (!is_null(static::$dashboardBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$dashboardBreadcrumbCallback, [$request, $this, Breadcrumb::make($dashboard?->label() ?? __("Dashboard"))]));
        }

        return Arr::wrap(Breadcrumb::make($dashboard?->label() ?? __("Dashboard")));
    }

    protected function resourceBreadcrumbs(NovaRequest $request, $resource, $breadcrumbArray) {

        if (method_exists($resource, "breadcrumbs")) {
            return Arr::wrap($resource->breadcrumbs($request, $this, $breadcrumbArray));
        }

        if (!is_null(static::$resourceBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$resourceBreadcrumbCallback, [$request, $this, $breadcrumbArray]));
        }

        return $breadcrumbArray;
    }

    protected function rootBreadcrumb(NovaRequest $request) {
        if (!is_null(static::$rootBreadcrumbCallback)) {
            return Arr::wrap(call_user_func_array(static::$rootBreadcrumbCallback, [$request, $this, Breadcrumb::make(__("Home"), config('nova.path', "/nova"))]));
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
