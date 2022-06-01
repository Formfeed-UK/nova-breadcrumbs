# Nova 4 Breadcrumbs

This [Laravel Nova](https://nova.laravel.com/) package adds automated breadcrumbs to the top of Nova 4 resources.

Whilst I have tested this package, I have not tested its interactions with every package out there, and in every environment possible. It currently has an 0.x version number due to its lack of tenure with regards to interactions outside of my environment more than anything else. 

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
- Customising the title static variable and label functions for resources
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

## Issues/Todo

- Make compatible with Dashboards and other non-resource based pages.
- Enable support for Polymorphic/ManyToMany relationships based upon previously visited resources

If you have any requests for functionality or find any bugs please open an issue or submit a Pull Request. Pull requests will be actioned faster than Issues.

## License

Nova Breadcrumbs is open-sourced software licensed under the [MIT license](LICENSE.md).
