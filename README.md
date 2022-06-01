# Nova 4 Breadcrumbs

This [Laravel Nova](https://nova.laravel.com/) package adds automated breadcrumbs to the top of Nova 4 resources

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

## Installation

1) Install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require formfeed-uk/nova-breadcrumbs
```



## Usage

### General

No additional configuration is required, the package will by default start working as soon as it is installed with all options enabled by default.

The theming classes are by default prefixed by the following:
- Components: component-
- Fields: field-
- Resources: resource-
- Nova Flexible Content Layout Groups: flex-group-

This can be changed in the configuration options (see below)

### Configuration Options

By default all theming options are enabled with the above default prefixes.

To configure which theming classes are displayed and their prefix, add the following to your `config/nova.php`

Note that the final delimiter in prefixes must be applied manually if required (to allow for empty string prefixes, or alternative prefix delimiters)

```php
// config/nova.php

return [

...

    'theming' => [
         'component' => true|false, // Enable/Disable the component classes
         'field' => true|false, // Enable/Disable the field name classes
         'resource' => true|false, // Enable/Disable the resource name classes
         'flex_group' => true|false, // Enable/Disable the Nova Flexible Content Layout Groups classes
         'prefix'=> [
            'component' => 'alternative-component-', // Component prefix
            'field' => 'alternative-field-', // Field prefix
            'resource' => 'alternative-resource-', // Resource prefix
            'flex_group' => 'alternative-flex-group-' // Nova Flexible Content Layout Group prefix
         ]
    ]

...

]
```

## License

Nova Resource Cards is open-sourced software licensed under the [MIT license](LICENSE.md).
