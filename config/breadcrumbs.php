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



    
];