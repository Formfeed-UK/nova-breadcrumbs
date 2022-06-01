<?php

namespace Formfeed\Breadcrumbs;


use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Http\Requests\NovaRequest;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Arr;
use Eminiarts\Tabs\Tabs;

use Formfeed\ResourceCards\ResourceCard;

use Illuminate\Database\Eloquent\Model;
use ErrorException;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    public function __construct(NovaRequest $request, Resource $resource = null) {
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
                $parentModel = ($parentResource::$model)::findOrFail($this->request->query('viaResourceId'));
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

            if ($model->id) {
                $relationship = [
                    'displayType' => 'detail',
                    'label' => $model->$title,
                    'resourceName' => $key,
                    'resourceId' => $model->id
                ];
                $array[] = $relationship;
            }

            if ($this->shouldLinkToParent($model, $novaClass)) {
                $indexCrumb = [
                    'displayType' => "span",
                    'label' => $label
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
                    'resourceName' => $key
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
            $relationships = $this->relationships($model);
            if (count($relationships) > 0) {
                foreach ($relationships as $key => $relationship) {
                    if ($relationship['type'] === "BelongsTo") {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected function getParentModel(Model $model) {
        if (method_exists($model, $this->getParentMethod())) {
            return $model->{$this->getParentMethod()};
        } 
        else {
            $relationships = $this->relationships($model);
            if (count($relationships) > 0) {
                foreach ($relationships as $key => $relationship) {
                    if ($relationship['type'] === "BelongsTo") {
                        return $model->$key;
                    }
                }
            }
        }
        return null;
    }

    protected function relationships(Model $model) {

        $relationships = [];

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

                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = [
                        'type' => (new ReflectionClass($return))->getShortName(),
                        'model' => (new ReflectionClass($return->getRelated()))->getName()
                    ];
                }
            } catch (ErrorException $e) {
            }
        }

        return $relationships;
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
