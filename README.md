Filterable
=======
This package gives you a convenient way to automatically filter Eloquent results based on query string parameters in the URL. Filterable parses the query string, compares it with columns that you'd like to automatically filter, then creates a dynamic scope that is used by Eloquent to construct the SQL.

* [Installation](#installation)
* [Copyright &amp; License](#license)
* [Usage](#usage)
    * [Single Value](#single-value)
    * [Multiple Values](#multiple-values)
    * [Multiple Parameters](#multiple-parameters)
    * [Boolean Operators](#boolean-operators)
    * [Comparison Operators](#comparison-operators)

<a name="installation"></a>
Installation
============
Add the package to 'require' in your composer.json file:

    "require": {
        "heroicpixels/filterable": "dev-master"
    },

Run 'composer dump-autoload' from the command line:

    #composer dump-autoload
    
Register the service provider in 'app/config/app.php'.  Service provider:

    'providers' => array(
        \\...
        'Heroicpixels\Filterable\FilterableServiceProvider',
        \\...
    );
<a name="license"></a>
License
=======
Copyright 2014 Dave Hodgins
Released under MIT license (http://opensource.org/licenses/MIT).  See LICENSE file for details.
<a name="usage"></a>
Usage
=====

NOTE: this package also includes a version (FilterableWrapper.php) that can be used to wrap a DB or Eloquent object, and a version (FilterableTrait.php) that can be used as a trait with an Eloquent model.

Filterable.php

Edit your Eloquent model to extend 'Heroicpixels\Filterable\Filterable'.

    class Object extends Heroicpixels\Filterable\Filterable {
        // ...
    }
    
FilterableWrapper.php

Give FilterableWrapper a DB or Eloquent object.

    $object = DB::table('objects');
    $objects = FilterableWrapper($object);

FilterableTrait.php

    class Object extends Eloquent {
    
       use Heroicpixels\Filterable\FilterableTrait;

    }
    
The examples below use the Filterable class!
   
In the above example, class Object corresponds to table 'objects':

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  1   |  red    |  square    |  150    |
|  2   |  blue   |  square    |  2000   |
|  3   |  green  |  circle    |  575    |
|  4   |  yellow |  triangle  |  15     |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |

Filterable Columns
------------------
Specify the column you want to automatically filter.

    $columns = [ 'color', 'shape', 'total' ];
    
For example:

     http://www.your-site/?color=blue&shape=round&total=500

You can also alias the columns if you prefer not to reveal them:

    $columns = [ 'col' => 'color', 'sha' => 'shape', 'tot' => 'total' ];

For example:

    http://www.your-site/?col=blue&sha=round&tot=500
    
To filter results, simply pass the columns to Eloquent using filterColumns():

    $objects = Object::filterColumns($columns)->get()->toArray();

You can also filter joins:

    $columns = array('color' => 'objects.color',
                     'name' => 'objects.name',
                     'shape' => 'objects.shape',
                     'category' => 'cat_object.cat_id');
    $objects = Object::join('cat_object', 'objects.id', '=', 'cat_object.object_id')
                       ->filterColumns($columns)
                       ->get()->toArray();

And you can filter eager loads:

    /**
     * Columns available in main query
     */
    $columns = array('color' => 'objects.color',
                     'name' => 'objects.name',
                     'shape' => 'objects.shape');
    $objects = Object::with(array('categories' => function($q) {
                   /**
                    * Columns available to sub-query
                    */
                   $columns = array('category' => 'cat_object.cat_id');
                   $q->filterColumns($columns);
               }))->filterColumns($columns)
               ->get()
               ->toArray();

The following examples demonstrate how query string parameters can be used.
<a name="single-value"></a>
Single Value
------------
    
    ?color=red

    SELECT ... WHERE ... color = 'red'

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  1   |  red    |  square    |  150    |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |
<a name="multiple-values"></a>
Multiple Values
---------------
    
    ?color[]=red&color[]=blue

    SELECT ... WHERE ... color = 'red' OR color = 'blue'

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  1   |  red    |  square    |  150    |
|  2   |  blue   |  square    |  2000   |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |
<a name="multiple-parameters"></a>
Multiple Parameters
-------------------

    ?color[]=red&shape[]=triangle

    SELECT ... WHERE ... color = 'red' AND shape = 'triangle'

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |
<a name="boolean-operators"></a>
Boolean Operators
-----------------
    
    ?color[]=red&shape[]=triangle&bool[shape]=or

    SELECT ... WHERE ... color = 'red' OR shape = 'triangle'

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  4   |  yellow |  triangle  |  15     |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |
<a name="comparison-operators"></a>
Comparison Operators
--------------------
**Greater Than**
    
    ?total=599&operator[total]=>

    SELECT ... WHERE ... total > '599'

|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  2   |  blue   |  square    |  2000   |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |

**Less Than**
    
    ?total=600&operator[total]=<

    SELECT ... WHERE ... total < '600'
    
|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  1   |  red    |  square    |  150    |
|  3   |  green  |  circle    |  575    |
|  4   |  yellow |  triangle  |  15     |

**Not Equal**
    
    ?shape=triangle&operator[shape]=!=

    SELECT ... WHERE ... shape != 'triangle'
    
|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  4   |  yellow |  triangle  |  15     |
|  5   |  red    |  triangle  |  900    |
|  6   |  red    |  triangle  |  600    |

**Between**
    
    ?total[start]=900&total[end]=5000

    SELECT ... WHERE ... total BETWEEN '900' AND '5000'
    
|  id  |  color  |  shape     |  total  |
|:-----|:--------|:-----------|:--------|
|  2   |  blue   |  square    |  2000   |
|  5   |  red    |  triangle  |  900    |

