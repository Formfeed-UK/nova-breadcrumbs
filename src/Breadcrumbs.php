<?php

namespace Formfeed\Breadcrumbs;


use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\BelongsTo as BelongsToField;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Arr;

use Eminiarts\Tabs\Tabs;

use Formfeed\ResourceCards\ResourceCard;

use ErrorException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;

class Breadcrumbs extends ResourceCard {
    /**
     * The width of the card (1/3, 1/2, or full).
     *
     * @var string
     */
    public $width = 'full';
    public $onlyOnDetail = null;
    public $height = "dynamic";
    public $resource;
    public array $extraClasses = [];

    public function __construct(NovaRequest $request, Resource $resource) {
        $this->resource = $resource;
        $this->request = $request ?? Request::instance();
        parent::__construct();
    }

    /**
     * Get the component name for the element.
     *
     * @return string
     */
    public function component() {
        return 'breadcrumbs';
    }

    public function withResource($resource) {
        $this->resource = $resource;
        return $this;
    }

    public function withClasses($classes) {
        $this->extraClasses = array_merge($this->extraClasses, Arr::wrap($classes));
        return $this;
    }

    protected function breadcrumbArray() {
        $primaryKey = $this->resource->model()->getKeyName();
        $currentModel = null;
        if (!$this->request->query('resourceId')) {
            if (is_null($this->resource) && $this->request->query('resourceName')) {
                $this->resource = Nova::resourceForKey($this->request->query('resourceName'));
            }
            $currentModel = new ($this->resource::$model);

            if ($this->request->query('viaResource') && $this->request->query('viaResource') !== "undefined" && $this->request->query('viaResourceId')) {
                $parentResource = Nova::resourceForKey($this->request->query('viaResource'));
                $parentModel = ($parentResource::$model)::find($this->request->query('viaResourceId'));
                $currentModel->{$this->getParentMethod()} = $parentModel;
            }
        } else {
            $currentModel = $this->resource->model()->query()->where($primaryKey, request('resourceId'))->first();
        }

        $array = [];

        $this->getRelationshipTree($currentModel, $array);
        $array[] = ['url' => config("nova.path", "/nova"), 'displayType' => 'home', 'label' => __("Home")];
        $array = array_reverse($array);
        if (($display = $this->request->query("display")) && in_array($this->request->query("display"), ["create", "update", "attach", "replicate"])) {
            $array[] = ['displayType' => 'span', 'label' => __(ucfirst($display))];
        }
        $array[array_key_last($array)]['displayType'] = 'span';
        return $array;
    }

    protected function getRelationshipTree($model, &$array) {
        if (!is_null($model)) {
            $novaClass = (string)Nova::resourceForModel($model);
            if (!class_exists($novaClass)) {
                return;
            }

            $label = __($novaClass::{config("breadcrumbs.label", "label")}());
            $title = $novaClass::${config("breadcrumbs.title", "title")};
            $key = $novaClass::uriKey();
            $base = rtrim(config("nova.path", "/nova"), "/");

            if ($model->id) {
                $currentResource = Nova::newResourceFromModel($model);
                $relationship = [
                    'displayType' => 'detail',
                    'label' => $currentResource->title(),
                    'resourceName' => $key,
                    'resourceId' => $model->id,
                    'base' => $base
                ];
                $array[] = $relationship;
            }

            if ($this->shouldLinkToParent($model, $novaClass)) {
                $indexCrumb = [
                    'displayType' => "span",
                    'label' => $label,
                    'base' => $base
                ];
                $tabsQuery = $this->getTabs($model, $novaClass);
                if (count($tabsQuery) > 0) {
                    $indexCrumb = array_merge($indexCrumb, $tabsQuery);
                } else {
                    $indexCrumb = array_merge($indexCrumb, $this->getParentCrumb($model, $novaClass));
                }
            } else {
                $indexCrumb = [
                    'displayType' => "index",
                    'label' => $label,
                    'resourceName' => $key,
                    'base' => $base
                ];
            }
            $array[] = $indexCrumb;
            $this->getRelationshipTree($this->getParentModel($model), $array);
        }
    }

    protected function shouldLinkToParent($model, $novaClass) {

        if (isset($novaClass::$linkToParent)) {
            return $novaClass::$linkToParent;
        }

        if (!is_null(config("breadcrumbs.linkToParent"))) {
            if(config("breadcrumbs.linkToParent") === true && $this->hasParentModel($model)) {
                return true;
            }
        }

        if (isset($novaClass::$displayInNavigation)) {
            return !$novaClass::$displayInNavigation;
        }

        return false;
    }

    protected function getParentCrumb($model, $novaClass = null) {
        if ($novaClass === null) {
            $novaClass = Nova::resourceForModel($model);
        }

        if (!is_null($this->getParentModel($model)?->id)) {
            $parentResource = Nova::newResourceFromModel($this->getParentModel($model));
            return [
                'resourceName' => $parentResource->uriKey(),
                'resourceId' => $this->getParentModel($model)?->id,
                'displayType' => 'detail'
            ];
        }
        return [];
    }

    protected function getParentMethod() {
        return config("breadcrumbs.parentMethod", "parent");
    }

    protected function getTabs($model, $novaClass = null) {

        if (!class_exists(Tabs::class) || is_null($this->getParentModel($model))) {
            return [];
        }

        if ($novaClass === null) {
            $novaClass = Nova::resourceForModel($model);
        }

        $parentResource = Nova::newResourceFromModel($this->getParentModel($model));
        $fields = $parentResource->fields($this->request);

        foreach ($fields as $field) {
            if (!$field instanceof Tabs) {
                continue;
            }

            foreach ($field->data as $tabField) {
                if (property_exists($tabField, 'resourceName') && $tabField->resourceName === $novaClass::uriKey() && !is_null($tabField->meta['tabSlug']) && $tabField->showOnDetail === true) {
                    $tab = $tabField;
                    $tabQuery = $field->slug ?? $this->getTabPreservedName($field);
                    break;
                }
            }
        }

        if (isset($tab)) {
            return [
                'tab' => $tab->meta['tabSlug'],
                'tabQuery' => $tabQuery ?? "tab",
                'resourceName' => $parentResource->uriKey(),
                'resourceId' => $this->getParentModel($model)?->id,
                'displayType' => 'detail'
            ];
        }
        return [];
    }

    protected function hasParentModel(Model $model) {
        if (method_exists($model, $this->getParentMethod())) {
            return true;
        } 
        else {
            $relationship = $this->relationships($model);
            if (!is_null($relationship) && method_exists($model, $relationship)) {
                return true;
            }
        }
        return false;
    }

    protected function getParentModel(Model $model) {
        if (method_exists($model, $this->getParentMethod())) {
            return $model->{$this->getParentMethod()};
        } 
        else {
            $relationship = $this->relationships($model);
            if (!is_null($relationship) && method_exists($model, $relationship)) {
                return $model->{$relationship};
            }
        }
        return null;
    }

    protected function relationships(Model $model) {
        return $this->relationshipsViaFields($this->resource, $this->request) ?? $this->relationshipsViaType($model) ?? $this->relationshipsViaInvoke($model, $this->resource) ?? null;
    }

    protected function relationshipsViaInvoke(Model $model, Resource $resource) {
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

    protected function relationshipsViaType(Model $model) {
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

    protected function getTabPreservedName($tab) {
        $reflection = new ReflectionObject($tab);
        $property = $reflection->getProperty('preservedName');
        $property->setAccessible(true);
        return ($property->getValue($tab));
    }

    protected function getClasses() {
        return array_merge(Arr::wrap(config("breadcrumbs.cssClasses", [])), $this->extraClasses);
    }

    public function jsonSerialize(): array {
        return array_merge(parent::jsonSerialize(), [
            "items" => $this->breadcrumbArray(),
            "extraClasses" => $this->getClasses()
        ]);
    }
}
