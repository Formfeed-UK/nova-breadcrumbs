# Nova 4 Breadcrumbs

This [Laravel Nova](https://nova.laravel.com/) package extends the breadcrumbs functionality of the First Party Nova breadcrumbs.

Tests repo can be found here: https://github.com/Formfeed-UK/nova-breadcrumbs-tests

## Version 2.x Changes

Version 2.x is a significant change from previous versions, this package now augments the existing nova breadcrumbs to offer:

- Static methods on the breadcrumbs class allowing control of breadcrumb generation globally
- Methods on Resources allowing control of breadcrumb generation per resource (per-resource methods override static callbacks)
- Support for resource groups
- Nested resource breadcrumbs

#### Breaking changes from 1.x
- Will use the Nova 4.19+ Breadcrumbs Vue components
- No Longer uses resource cards (This gives better UX as the breadcrumbs will be sent via the page props as per the built in ones and drops a request)
- Will intercept the Nova Breadcrumbs via middleware
- Can no longer have custom CSS (due to using the Nova components)
- Can no longer use the onlyOn{view}, exceptOn{view} etc permissions methods. Breadcrumb visibility can now be controlled via the callbacks/class methods
- Each breadcrumb will extend the Nova Breadcrumb class, and the array of Creadcrumbs will extend the Nova Breadcrumbs class.

## Requirements >= v2.x

- `php: >=8.0`
- `laravel/nova: ^4.19`

## Requirements <= v1.x

- `php: >=8.0`
- `laravel/nova: ^4.0`
- `formfeed-uk/nova-resource-cards: ^1.1`

## Features

This package adds automated breadcrumbs to the top of Nova 4 resources.

It supports:

- belongsTo relationships to build a full set of breadcrumbs to your Nova root.
- Automatic detection of belongsTo relationships, and the ability to specify a relationship as the "parent" relationship
- Linking directly to Resource Tabs in the [eminiarts/nova-tabs](https://github.com/eminiarts/nova-tabs) package (Tab slugs are recommended)
- Linking to either the resource's index or its parent (for relationships included as fields)
- Customising the title and label functions for resources
- Use on Dashboards (only to the extent that the Breadcrumbs show as `Home -> {Current Dashboard}`). Mainly for UI consistency. 
- Methods/Callbacks to control the breadcrumbs generation globall or per resource

## Installation

1) Install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require formfeed-uk/nova-breadcrumbs
```
2) Publish the config file (optional)

```bash
php artisan vendor:publish --tag=breadcrumbs-config
```

## Usage

### General

1) Enable Nova Breadcrumbs in the same way as the first party Nova Breadcrumbs in your `NovaServiceProvider` `boot` method:

```php

    public function boot() {
        parent::boot();

        Nova::withBreadcrumbs(true);
    }

```

2) Optionally configure a `parent` method on your Model to explicitly define the relationship the package should query. The name of this function can be changed in the configuration file.

```php

class MyModel extends Model {

    ...

    public function parent() {
        return $this->config();
    }   

    public function config() {
        return $this->belongsTo(Config::class, "config_id");
    }

    ...

}
```

### Resource Methods

You can optionally override the default behaviour of the breadcrumbs package on a per resource basis by adding methods to your Nova Resource. These methods should all return an instance of Breadcrumb or an array of Breadcrumb instances.

- `groupBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $groupBreadcrumb)` - Override the group breadcrumb for this resource
- `indexBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $indexBreadcrumb)` - Override the index breadcrumb for this resource
- `detailBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $detailBreadcrumb)` - Override the detail breadcrumb for this resource
- `formBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $formBreadcrumb, $type)` - Override the form breadcrumb for this resource, $type is a string referring to the current form type (create, update, attach, replicate etc)
- `resourceBreadcrumbs(NovaRequest $request, Breadcrumbs $breadcrumbs, array $breadcrumbArray)` - Override the entire set of breadcrumbs for this resource

For Dashboards, you can use the following method:

- `dashboardBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $dashboardBreadcrumb)` - Override the dashboard breadcrumb for this resource

#### Example

```php

class MyResource extends Resource {

    // Change the name of the breadcrumb
    public function detailBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $detailBreadcrumb) {
        return $detailBreadcrumb->name = _('My Custom Name');
    }

    // Remove all previous breadcrumbs and add a new root
    public function resourceBreadcrumbs(NovaRequest $request, Breadcrumbs $breadcrumbs, array $breadcrumbArray) {
        $breadcrumbs->items = [Breadcrumb::make('Home', '/')];
        return $breadcrumbArray;
    }

    // Prevent the group breadcrumb for this resource
    public function groupBreadcrumb(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $groupBreadcrumb) {
        return null;
    }
}

```

### Static Callbacks

You can override the default behaviour of the breadcrumbs globally by using the following static methods on the Breadcrumbs class. They should be provided within a boot method on a service provider.

These methods will be overriden by any per resource methods.

The closure provided should return either an instance of Breadcrumb or an array of Breadcrumb instances.

- detailCallback(callable $callback)
- indexCallback(callable $callback)
- formCallback(callable $callback)
- resourceCallback(callable $callback)
- dashboardCallback(callable $callback)
- rootCallback(callable $callback)
- groupCallback(callable $callback)

#### Example 

```php

use FormFeed\Breadcrumbs\Breadcrumbs;
use FormFeed\Breadcrumbs\Breadcrumb;

class NovaServiceProvider extends ServiceProvider {

    public function boot() {
        parent::boot();

        Nova::withBreadcrumbs(true);

        Breadcrumbs::detailCallback(function(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $detailBreadcrumb) {
            return $detailBreadcrumb->name = _('My Custom Name');
        });

        Breadcrumbs::rootCallback(function(NovaRequest $request, Breadcrumbs $breadcrumbs, Breadcrumb $rootBreadcrumb) {
            return Breadcrumb::make(_('My Custom Root Breadcrumb'), "/my-root");
        });
    }
}

```

### Configuration Options

Please see the included config file for a full list of configuration options (it's well commented).

In addition to these options you can also specify the following options in the resource itself:

#### Link To parent 
This determines if the breadcrumb should link to the parent resource regardless of if the current resource's index is navigable from the main menu:

`public static $linkToParent = true|false;`

#### Disable Parent Breadcrumbs
Resolving Parent breadcrumbs can be disabled by adding the following static variable to a resource:

`public static $resolveParentBreadcrumbs = false;`

#### Invoking Reflection
Determining the parent via invoking reflected blank model methods and checking the returned type is now disabled by default.

It is highly recommended that this functionality be left off, and either a parent method, form field, or a defined relationship return type be used instead. 

However if needed you can still enable this functionality by doing the following:

- If downloading for the first time after 1.0.0 is released, set the `invokingReflection` configuration option after publishing the config
- If upgrading from 0.1.x add the following to your config: `"invokingReflection" => true`

You can also set this on a per-resource basis with the following static:

`public static $invokingReflection = true|false;`

## Issues/Todo

- Enable support for Polymorphic/ManyToMany relationships based upon previously visited resources

If you have any requests for functionality or find any bugs please open an issue or submit a Pull Request. Pull requests will be actioned faster than Issues.

## License

Nova Breadcrumbs is open-sourced software licensed under the [MIT license](LICENSE.md).
