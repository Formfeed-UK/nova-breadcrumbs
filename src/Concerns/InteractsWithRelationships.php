<?php

namespace Formfeed\Breadcrumbs\Concerns;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Laravel\Nova\Fields\BelongsTo as BelongsToField;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use ReflectionClass;
use ReflectionMethod;

trait InteractsWithRelationships {

    protected function relationships(Resource $resource, NovaRequest $request = null) {
        $model = $resource->model();
        $request ??= NovaRequest::createFromGlobals();
        return $this->relationshipsViaMethod($model) ?? $this->relationshipsViaFields($resource, $request) ?? $this->relationshipsViaType($model) ?? $this->relationshipsViaInvoke($model, $resource) ?? null;
    }

    protected function relationshipsViaMethod($model) {
        if ($model->exists && method_exists($model, $this->getParentMethod())) {
            return $this->getParentMethod();
        }
        return null;
    }

    protected function relationshipsViaInvoke(Model|Null $model, Resource $resource) {
        $invoke = (property_exists($resource::class, 'invokingReflection') ? $resource::class::$invokingReflection : config("breadcrumbs.invokingReflection", false)) ?? false;
        if ($invoke !== true) {
            return null;
        }
        $model = new (get_class($model));
        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__
            ) {
                continue;
            }

            try {
                $return = $method->invoke($model);
                $name = (new ReflectionClass($return))->getShortName();
                if ($return instanceof Relation && $name === "BelongsTo") {
                    return $method->getShortName();
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    protected function relationshipsViaType(Model|Null $model) {
        $model = new (get_class($model));
        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__
            ) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!is_null($returnType) && $returnType->getName() === BelongsTo::class) {
                return $method->getShortName() ?? null;
            }
        }

        return null;
    }

    protected function relationshipsViaFields(Resource $resource, NovaRequest $request) {
        $fields = $resource->availableFields($request);
        $filtered = $fields->filter(function ($value, $key) {
            return ($value instanceof BelongsToField);
        });
        if ($filtered->count() > 0) {
            $belongsTo = $filtered->first();
            return $belongsTo->belongsToRelationship ?? null;
        }
        return null;
    }
}
