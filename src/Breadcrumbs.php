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

    protected function breadcrumbArray() {
        $primaryKey = $this->resource->model()->getKeyName();
        $currentModel = null;
        if (!$this->request->query('resourceId')) {
            if (is_null($this->resource) && $this->request->query('resourceName')) {
                $this->resource = Nova::resourceForKey($this->request->query('resourceName'));
            }
            $currentModel = new ($this->resource::$model);

            if ($this->request->query('viaResource') && $this->request->query('viaResource') !== "undefined") {
                $parentResource = Nova::resourceForKey($this->request->query('viaResource'));
                $parentModel = ($parentResource::$model)::findOrFail($this->request->query('viaResourceId'));
                $currentModel->parent = $parentModel;
            }
        } else {
            $currentModel = $this->resource->model()->query()->where($primaryKey, request('resourceId'))->first();
        }

        $array = [];

        //dd($this->relationships($currentModel));

        $this->getRelationshipTree($currentModel, $array);
        $array[] = ['displayType' => 'home', 'label' => "Home"];
        $array = array_reverse($array);
        if (($display = $this->request->query("display")) && in_array($this->request->query("display"), ["create", "update", "attach", "replicate"])) {
            $array[] = ['displayType' => 'span', 'label' => ucfirst($display)];
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

            $label = $novaClass::label();
            $key = $novaClass::uriKey();
            $title = $novaClass::$title;
            $title_display = $novaClass::$title . "_display";

            if ($model->id) {
                $relationship = [
                    'displayType' => 'detail',
                    'label' => $model->$title_display ?? $model->$title,
                    'resourceName' => $key,
                    'resourceId' => $model->id
                ];
                $array[] = $relationship;
            }

            if (is_null($novaClass::$displayInNavigation) || $novaClass::$displayInNavigation !== false) {
                $indexCrumb = [
                    'displayType' => "index",
                    'label' => $label,
                    'resourceName' => $key
                ];
            } else {
                $indexCrumb = [
                    'displayType' => "span",
                    'label' => $label
                ];
                $indexCrumb = array_merge($indexCrumb, $this->getTabQuery($model, $novaClass));
            }
            $array[] = $indexCrumb;
            $this->getRelationshipTree($model->parent, $array);
        }
    }

    protected function getTabQuery($model, $novaClass = null) {

        if (!class_exists(Tabs::class) || is_null($model->parent)) {
            return [];
        }

        if ($novaClass === null) {
            $novaClass = Nova::resourceForModel($model);
        }

        $parentResource = Nova::newResourceFromModel($model->parent);
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
                'resourceId' => $model->parent->id,
                'displayType' => 'detail'
            ];
        }

        return [];
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
        return($property->getValue($tab));
    }

    protected function encodeURI($string) {
        $unescaped = array(
            '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', '%7E'=>'~',
            '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
        );
        $reserved = array(
            '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
            '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
        );
        $score = array(
            '%23'=>'#'
        );
        return strtr(rawurlencode($string), array_merge($reserved,$unescaped,$score));
    }

    public function jsonSerialize(): array {
        return array_merge(parent::jsonSerialize(), [
            "items" => $this->breadcrumbArray(),
        ]);
    }
}
