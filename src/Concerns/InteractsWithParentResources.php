<?php

namespace Formfeed\Breadcrumbs\Concerns;

use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

trait InteractsWithParentResources {

    protected function hasParentResource(Resource $resource) {
        return (!is_null($this->getParentResource($resource)));
    }

    protected function getParentResource(Resource $resource) {
        $model = $resource->model();
        $relationship = $this->relationships($resource);

        if (!is_null($relationship) && method_exists($model, $relationship) && !is_null($model->{$relationship})) {
            return Nova::newResourceFromModel($model->{$relationship});
        }
        return null;
    }

    protected function getParentMethod() {
        return config("breadcrumbs.parentMethod", "parent");
    }

}
