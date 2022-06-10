# Nova 4 Breadcrumbs

This [Laravel Nova](https://nova.laravel.com/) package adds automated breadcrumbs to the top of Nova 4 resources.

Whilst I have tested this package, I have not tested its interactions with every package out there, and in every environment possible. It currently has an 0.x version number due to its lack of tenure with regards to interactions outside of my environment more than anything else. 

## 1.0.0 Breaking Changes

1.0.0 introduces a breaking change in how the parent model is determined. The following is now applied in order:

1) Attempt to get parent from the model method with name retrieved from `parentMethod` config option
2) Attempt to get parent from the first `Laravel\Nova\Fields\BelongsTo` field in your resource
3) Attempt to get parent from the first method on your model that has a defined return type of `Illuminate\Database\Eloquent\Relations\BelongsTo`

If none of these are fulfilled the package now optionally (defaulting to false):

4) Attempt to find a `Laravel\Nova\Fields\BelongsTo` relationship via creating a blank model, invoking it's methods, and checking the type of the return.

This was previously turned on by default if the `parentMethod` was not found, and had the potential to be destructive if there were methods on the model which were destructive across all records, such as `Model::truncate()`

Please see the **Configuration Options** section below for more details on how to enable this functionality. 

## Requirements

- `php: >=8.0`
- `laravel/nova: ^4.0`
- `formfeed-uk/nova-resource-cards: ^1.0`

## Features

This package adds automated breadcrumbs to the top of Nova 4 resources.

It supports:

- belongsTo relationships to build a full set of breadcrumbs to your Nova root.
- Automatic detection of belongsTo relationships, and the ability to specify a relationship as the "parent" relationship
- Linking directly to Resource Tabs in the [eminiarts/nova-tabs](https://github.com/eminiarts/nova-tabs) package (Tab slugs are recommended)
- Linking to either the resource's index or its parent (for relationships included as fields)
- Customising the title and label functions for resources
- Specifying custom css classes

This package relies on [formfeed-uk/nova-resource-cards](https://github.com/Formfeed-UK/nova-resource-cards) which wrap a number of nova pages. If you override these pages yourself ensure that nova-resource-cards is loaded after the packages which do so. 

Please note that this package is currently **not** compatible with dashboards.

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

1) Include the breadcrumbs card at the beginning of the cards array in any resources that you wish to use breadcrumbs (be sure to include `$this` as the second parameter):

```php
...

use Formfeed\Breadcrumbs\Breadcrumbs;

class MyResource extends Resource {
    ...
    public function cards(NovaRequest $request) {
        return [
            Breadcrumbs::make($request, $this),
            ...
        ];
    }
    ...
}
```
This card extends `ResourceCard` from that package and as such has the visbility and authorisation methods available. 

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

### Include in all Resources

If you would like to include the Breadcrumbs on all resources in one place, the best way to do this is to override the `resolveCards` method on your `App/Nova/Resource.php` file, as by default this file is extended by all of the Nova resources in your Application.

A very basic example could look like this:

```php
    public function resolveCards(NovaRequest $request)
    {
        $cards = $this->cards($request);
        array_unshift($cards, Breadcrumbs::make($request, $this));
        return collect(array_values($this->filter($cards)));
    }
```

### Configuration Options

Please see the included config file for a full list of configuration options (it's well commented).

In addition to these options you can also specify the following options in the resource itself:

#### Link To parent
This determines if the breadcrumb should link to the parent resource regardless of if the current resource's index is navigable from the main menu:

`public static $linkToParent = true|false;`

#### Extra CSS Classes
These can be configured globally or chained to the Breadcrumbs class:

`Breadcrumbs::make($request, $this)->withClasses(["my-extra", "classes"])`

#### Visibility and Authorisations
Please see the [formfeed-uk/nova-resource-cards](https://github.com/Formfeed-UK/nova-resource-cards) package for information on the visbility and authorisation methods. They broadly follow the same pattern as fields.

#### Invoking Reflection
Determining the parent via invoking reflected blank model methods and checking the returned type is now disabled by default.

It is highly recommended that this functionality be left off, and either a parent method, form field, or a defined relationship return type be used instead. 

However if needed you can still enable this functionality by doing the following:

- If downloading for the first time after 1.0.0 is released, set the `invokingReflection` configuration option after publishing the config
- If upgrading from 0.1.x add the following to your config: `"invokingReflection" => true`

You can also set this on a per-resource basis with the following static:

`public static $invokingReflection = true|false;`

## Issues/Todo

- Make compatible with Dashboards and other non-resource based pages.
- Enable support for Polymorphic/ManyToMany relationships based upon previously visited resources

If you have any requests for functionality or find any bugs please open an issue or submit a Pull Request. Pull requests will be actioned faster than Issues.

## License

Nova Breadcrumbs is open-sourced software licensed under the [MIT license](LICENSE.md).
