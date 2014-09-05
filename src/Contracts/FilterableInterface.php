<?php namespace Heroicpixels\Filterable;

/**
 * This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
interface FilterableInterface
{

    /**
     * Get all the available bools
     *
     * @return array
     */
    public function getAvailableBools();


    /**
     * Get all the available operators
     *
     * @return array
     */
    public function getAvailableOperators();

    /**
     * Set the default bool
     *
     * @param $bool
     * @return bool
     */
    public function setDefaultBool($bool);


    /**
     * Get the default bool
     *
     * @return string
     */
    public function getDefaultBool();


    /**
     * Set the default operator
     *
     * @param $operator
     * @return bool
     */
    public function setDefaultOperator($operator);


    /**
     * Get the default operator
     *
     * @return string
     */
    public function getDefaultOperator();


    /**
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
    public function setFilterColumns(Array $columns, $append = true, $validate = true);


    /**
     * Get the filter columns array
     *
     * @return array
     */
    public function getFilterColumns();

    /**
     * Return the filter array
     *
     * @return array
     */
    public function getFilters();


    /**
     * @return array
     */
    public function getQueryValues();


    /**
     *
     * @param $queryString
     * @return $this
     */
    public function setQueryValues($queryString);


    /**
     * Add an new filter to the filter array
     *
     * @param string $column
     * @param array  $values
     * @param string $bool
     * @param string $operator
     * @return array
     * @throws InvalidArgumentException
     */
    public function addFilter($column, $values, $bool = null, $operator = null);


    /**
     * @param $query
     * @param null $columns
     * @param bool $validateColumns
     * @param null $queryString
     * @return mixed
     */
    public function scopeFilterColumns($query, $columns = null, $validateColumns = true, $queryString = null);


}
