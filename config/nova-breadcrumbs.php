<?php

return [

        /*
        |--------------------------------------------------------------------------
        | Always Link to Parent
        |--------------------------------------------------------------------------
        |
        | Where a resource is both in the main navigation and exists as a
        | belongsTo field on its parent, always link to the parent instead of
        | the index view for that resource
        |
        | Default Value: false
        | Default Behaviour:
        | Where a resource is in the main navigation, links to that resource will
        | go to its index view.
        |
        | Can be overridden on a per resource basis with:
        | public static $linkToParent = true|false;
        |
        */
        "linkToParent" => false,

        /*
        |--------------------------------------------------------------------------
        | Model Parent method
        |--------------------------------------------------------------------------
        |
        | The name of the optional method on the resources model that defines the
        | parent model
        |
        | Default Value: parent
        |
        */
        "parentMethod" => "parent",

        /*
        |--------------------------------------------------------------------------
        | Use Invoking Reflection
        |--------------------------------------------------------------------------
        |
        | If the package can't get the parent relationship from either a specified
        | "parent" method, a Nova/Fields/BelongsTo field, or a method with BelongsTo
        | return type on the underlying model, it will attempt to find the parent
        | via creating a new blank model, invoking the methods, and reading the
        | response types.
        |
        | If your model methods are designed reasonably, this should present no issue
        | however if your model methods have destructive side effects for any reason
        | such as "truncate" this could be problematic behaviour.
        |
        | As such from 1.0.0 this behaviour is disabled globally by default.
        |
        | It can be enabled globally by setting this to true, or on a per-resource
        | basis by setting the following static on the resource:
        |
        | public static $invokingReflection = true|false;
        |
        | Default Value: false
        |
        */
        "invokingReflection" => false,

        /*
        |--------------------------------------------------------------------------
        | Title Static Property
        |--------------------------------------------------------------------------
        |
        | The static property on a nova resource that stores the "title" column to
        | use as the label for resource record crumbs.
        |
        | Default: "title"
        |
        */
        "titleProperty" => "title",

        /*
        |--------------------------------------------------------------------------
        | Label static method
        |--------------------------------------------------------------------------
        |
        | The resource static method to use to determine the label and
        | singular label for the resource name
        |
        | Default:
        | labelFunction: "label"
        | singularLabelFunction: "singularLabel"
        |
        */
        "labelFunction" => "label",
        "singularLabelFunction" => "singularLabel",

        /*
        |--------------------------------------------------------------------------
        | Include Resource Group in Breadcrumbs Array
        |--------------------------------------------------------------------------
        |
        | Determines whether to include the group as defined by the group static
        | function on the array (where present)
        |
        | Default: false
        |
        */
        "includeGroup" => false,

        /*
        |--------------------------------------------------------------------------
        | Ignore default "Other" group
        |--------------------------------------------------------------------------
        |
        | Nova automatically assigns a group of "Other", ignore this group in
        | breadcrumbs generation
        |
        | Default: false
        |
        */
        "includeOtherGroup" => false,


];
