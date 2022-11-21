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

    public function build(NovaRequest $request = null) {
        $this->request = $request ?? app()->make(NovaRequest::class);
        $this->resource = $this->request->newResourceWith($this->request->findModel($this->request->resourceId));
        $this->items = [];
        $this->breadcrumbArray();
        return $this;
    }

    protected function breadcrumbArray() {

        if ($this->resource instanceof Dashboard) {
            return $this->dashboardArray();
        }

        if (!$this->resource->model()->exists && ($this->request->viaResource && $this->request->viaResourceId)) {
            $resource = $this->request->findParentResource();
        } else {
            $resource = $this->resource;
        }

        $this->getRelationshipTree($resource);

        $this->items[] = Breadcrumb::make(__("Home"), config('nova.path', "/nova"));

        $this->items = array_reverse($this->items);

        $this->items[] = $this->crudBreadcrumb($this->request, $resource);

    }

    protected function dashboardArray() {
        return [
            Breadcrumb::make(__("Home"), config('nova.path', "/nova")),
            Breadcrumb::make($this->resource->label()),
        ];
    }

    protected function getRelationshipTree($resource) {

        if (is_null($resource)) {
            return;
        }

        $novaClass = $resource::class;

        $this->items[] = Breadcrumb::resource($resource);
        if ($resource->model()->exists) {
            $this->items[] = Breadcrumb::indexResource($resource);
        }


        if (!property_exists($novaClass, "resolveParentBreadcrumbs") || $novaClass::$resolveParentBreadcrumbs !== false) {
            $resource = $this->getParentResource($resource);
            $this->getRelationshipTree($resource);
        };
    }

    protected function crudBreadcrumb($request, $resource) {
        $type = $this->pageType($request);
        if (!is_null($type) && !in_array($type, ["index", "detail", "dashboard"])) {
            return Breadcrumb::make(__(Str::ucfirst($type)), null);
        }
    }

    protected function pageType(NovaRequest $request) {
        $controller = $request->route()->getController();
        switch($controller::class) {
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

    public function jsonSerialize(): array
    {
        return $this->authorizedToSee(app(NovaRequest::class))
            ? array_filter($this->items)
            : [];
    }

}
