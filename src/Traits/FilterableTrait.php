<?php namespace Heroicpixels\Filterable;

/**
 * This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */


/**
 * A trait for dynamically filtering Eloquent models based on query string parameters.
 *
 * @package Heroicpixels\Filterable
 */
trait FilterableTrait
{

    /**
     * Columns that can be dynamically filtered from the query string
     *
     * @var array
     */
    protected $filterColumns = [];

    /**
     * Contains all the filters that will be applied
     *
     * @var array
     */
    protected $filters = [];

    /**
     * All possible bools
     *
     * @var array
     */
    protected $availableBools = [ 'and' => 'where', 'or' => 'orWhere' ];

    /**
     * All the possible operators
     *
     * @var array
     */
    protected $availableOperators = [ '=', '<', '>', '!=','like' ];

    /**
     * Default operator
     *
     * @var string
     */
    protected $defaultOperator = '=';

    /**
     * Default bool
     *
     * @var string
     */
    protected $defaultBool = 'where';

    /**
     * Parsed query string items
     *
     * @var array
     */
    protected $queryValues = [];


    /**
     * Get all the available bools
     *
     * @return array
     */
    public function getAvailableBools()
    {
        return $this->availableBools;
    }


    /**
     * Get all the available operators
     *
     * @return array
     */
    public function getAvailableOperators()
    {
        return $this->availableOperators;
    }


    /**
     * Set the default bool
     *
     * @param $bool
     * @return $this
    */
    public function setDefaultBool($bool)
    {
        if ( isset($this->getAvailableBools()[$bool]) ) {
            $this->defaultBool = $bool;
        }

        return $this;
    }


    /**
     * Get the default bool
     *
     * @return string
     */
    public function getDefaultBool()
    {
        return $this->defaultBool;
    }


    /**
     * Set the default operator
     *
     * @param $operator
     * @return $this
     */
    public function setDefaultOperator($operator)
    {
        if (in_array($operator, $this->getAvailableOperators())) {
            $this->defaultOperator = $operator;
        }

        return $this;
    }


    /**
     * Get the default operator
     *
     * @return string
     */
    public function getDefaultOperator()
    {
        return $this->defaultOperator;
    }


    /**
     * Get the filter columns array
     *
     * @return array
     */
    public function getFilterColumns()
    {
        return $this->filterColumns;
    }


    /**
     * Return the filter array
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }


    /**
     * Get the query values
     *
     * @return array
     */
    public function getQueryValues()
    {
        return $this->queryValues;
    }


    /**
     *
     * Specify the columns that can be dynamically filtered from the query string.
     * Columns can be referenced directly - array('column1', 'column2') - or they can
     * be aliased - array('col1' => 'column1', 'co2' => 'column2').
     *
     * @param array $columns The columns to use
     * @param bool $append Append or overwrite existing values
     * @param bool $validate Should the columns be validated
     * @return $this
     *
     */
    public function setFilterColumns(Array $columns, $append = true, $validate = true)
    {
        if ( count(array_filter(array_keys($columns), 'is_string')) == 0 ) {
            // Numeric indexes, so build new associative index array
            $columns = array_combine($columns, $columns);
        }

        if ( $validate ) {
            $tableColumns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
            $columns = array_intersect($columns, $tableColumns);
        }

        $this->filterColumns = ( ! $append ) ? $columns : array_merge($this->filterColumns, $columns);

        return $this;
    }


    /**
     * Parse the querystring
     *
     * @param $queryString
     * @return $this
     */
    public function setQueryValues($queryString)
    {
        parse_str($queryString, $this->queryValues);

        // Loop through the queryValues
        foreach ($this->getQueryValues() as $key => $values) {

            if ( ! isset($this->getFilterColumns()[$key]) ) {
                continue;
            }

            // Bool
            if (isset($this->getQueryValues()['bool'][$key])) {
                $bool = $this->getQueryValues()['bool'][$key];
            } else {
                $bool = $this->getDefaultBool();
            }

            // Operator
            if ( isset($this->getQueryValues()['operator'][$key]) ) {
                $operator = $this->getQueryValues()['operator'][$key];
            } else {
                $operator = $this->getDefaultOperator();
            }

            $this->addFilter($this->getFilterColumns()[$key], $values, $bool, $operator);
        }

        return $this;
    }


    /**
     * Add an new filter to the filter array
     *
     * @param string $column
     * @param array  $values
     * @param string $bool
     * @param string $operator
     * @return array
     * @throws \InvalidArgumentException
     */
    public function addFilter($column, $values, $bool = null, $operator = null)
    {
        if ( ! in_array($column, $this->getFilterColumns()) ) {
            throw new \InvalidArgumentException('The column does not exists in the filter columns array');
        }

        // Set values
        $this->filters[$column]['values'] = $values;

        // Bool
        if (isset($bool) && isset($this->getAvailableBools()[$bool]) ) {
            $this->filters[$column]['bool'] = $this->getAvailableBools()[$bool];
        } else {
            $this->filters[$column]['bool'] = $this->getDefaultBool();
        }

        //  Operator
        if (isset($operator) && in_array($operator, $this->getAvailableOperators())) {
            $this->filters[$column]['operator'] = $operator;
        } else {
            $this->filters[$column]['operator'] = $this->getDefaultOperator();
        }

        return $this->filters[$column];
    }


    /**
     * Eloquent filterColums queryscope
     *
     * @param $query
     * @param null $columns
     * @param bool $validateColumns
     * @param null $queryString
     * @return mixed
     */
    public function scopeFilterColumns($query, $columns = null, $validateColumns = true, $queryString = null)
    {
        if ( ! is_null($columns) ) {
            $this->setFilterColumns($columns, true, $validateColumns);
        }

        if (  ! is_null($queryString) ) {
            $this->setQueryValues($queryString);
        } elseif ( empty($this->getQueryValues()) ) {
            $this->setQueryValues($_SERVER['QUERY_STRING']);
        }

        if ( empty($this->getFilterColumns()) ||
             empty($this->getFilters())
        ) {
            return $query;
        }

        // Loop through the filters
        foreach ($this->getFilters() as $key => $value) {

            if ( is_string($value['values']) ) {
                // a = b
                $query->{$value['bool']}($key, $value['operator'], $value['values']);
            } else {
                // Between a AND b
                if (isset($value['values']['start']) && isset($value['values']['end']) ) {
                    $query->whereBetween($key, [$value['values']['start'], $value['values']['end']]);
                } else {
                    // a = b OR c = d OR ...
                    $query->{$value['bool']}(function ($q) use ($key, $value, $query) {
                        foreach ($value['values'] as $valueKey => $val) {
                            $q->orWhere($key, $value['operator'], $val);
                        }
                    });
                }
            }
        }

        return $query;
    }


}
