<?php

namespace Formfeed\Breadcrumbs;

use Laravel\Nova\Menu\Breadcrumb as NovaBreadcrumb;
use Laravel\Nova\Resource;
use Laravel\Nova\Nova;
use Laravel\Nova\Http\Requests\NovaRequest;

use Eminiarts\Tabs\Tabs;

use Formfeed\Breadcrumbs\Concerns\InteractsWithParentResources;
use Formfeed\Breadcrumbs\Concerns\InteractsWithRelationships;

use ReflectionObject;

/**
 * @method static static make($name, $path = null)
 **/

class Breadcrumb extends NovaBreadcrumb {

    use InteractsWithParentResources;
    use InteractsWithRelationships;

    public function __construct($name, $path = null, $request = null) {
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * Create a breadcrumb from a resource class.
     *
     * @param  \Laravel\Nova\Resource|class-string<\Laravel\Nova\Resource>  $resourceClass
     * @return static
     */

    public static function resource($resourceClass) {
        if ($resourceClass instanceof Resource && $resourceClass->model()->exists === true) {
            return static::detailResource($resourceClass);
        }

        return static::indexResource($resourceClass);
    }

    public static function indexResource($resourceClass) {

        $breadcrumb = static::make(Nova::__($resourceClass::{static::label()}()))
                        ->path('/resources/' . $resourceClass::uriKey())
                        ->canSee(function ($request) use ($resourceClass) {
                            return $resourceClass::availableForNavigation($request) && $resourceClass::authorizedToViewAny($request);
                        });



        if ($breadcrumb->shouldLinkToParent($resourceClass)) {
            $tabsQuery = $breadcrumb->getTabs($resourceClass);
            if ($tabsQuery) {
                $breadcrumb->path = $tabsQuery;
                $breadcrumb->canSee(function ($request) use ($resourceClass) {
                    return $resourceClass::authorizedToViewAny($request);
                });
            } else {
                $breadcrumb->path = $breadcrumb->getParentBreadcrumb($resourceClass);
            }
            return $breadcrumb;
        } else {
            return $breadcrumb;
        }
    }

    public static function detailResource($resourceClass) {
        return static::make(__($resourceClass->{static::title()}()))
        ->path('/resources/' . $resourceClass::uriKey() . '/' . $resourceClass->getKey())
        ->canSee(function ($request) use ($resourceClass) {
            return $resourceClass->authorizedToView($request);
        });
    }

    public function shouldLinkToParent($resource) {

        $novaClass = is_string($resource) ? $resource : $resource::class;

        if (isset($novaClass::$linkToParent)) {
            return $novaClass::$linkToParent;
        }

        if (!is_null(config("nova-breadcrumbs.linkToParent"))) {
            if (config("nova-breadcrumbs.linkToParent") === true && $this->hasParentResource($resource)) {
                return true;
            }
        }

        if (isset($novaClass::$displayInNavigation)) {
            return !$novaClass::$displayInNavigation;
        }

        return false;
    }

    public function getParentBreadcrumb($resource) {
        $novaClass = $resource::class;
        $parentResource = $this->getParentResource($resource);

        if (!is_null($parentResource) && $parentResource->model()->exists) {
            return "/resources/" . $parentResource->uriKey() . "/" . $parentResource->model()->getKey();
        }
        return null;
    }

    public function getTabs(Resource $resource) {
        $novaClass = $resource::class;

        $parentResource = $this->getParentResource($resource);

        if (!class_exists(Tabs::class) || is_null($parentResource)) {
            return null;
        }

        $fields = $parentResource->fields(NovaRequest::createFromGlobals());

        foreach ($fields as $field) {
            if (!$field instanceof Tabs) {
                continue;
            }

            $tabQuery = null;
            foreach ($field->data as $tabField) {
                if (property_exists($tabField, 'resourceName') && $tabField->resourceName === $novaClass::uriKey() && !is_null($tabField->meta['tabSlug']) && $tabField->showOnDetail === true) {
                    $tab = $tabField;
                    $tabQuery = $field->slug ?? $this->getTabPreservedName($field) ?? "tab";
                    break;
                }
            }
        }

        if (isset($tab)) {
            return "/resources/" . $parentResource->uriKey() . "/" . $parentResource->model()->getKey() . "#{$tabQuery}={$tab->meta['tabSlug']}";
        }

        return null;
    }

    protected static function label() {
        return config("nova-breadcrumbs.label", "label");
    }

    protected static function title() {
        return config("nova-breadcrumbs.title", "title");
    }

    public function getTabPreservedName($tab) {
        $reflection = new ReflectionObject($tab);
        $property = $reflection->getProperty('preservedName');
        $property->setAccessible(true);
        return ($property->getValue($tab));
    }
}
