<?php namespace Heroicpixels\Filterable\Contracts;

/**
 * Interface FilterableModelInterface
 * @package Heroicpixels\Filterable\Contracts
 */
interface FilterableModelInterface
{
    /**
     *	Setup data structures and default values
     */
    public function resetFilterableOptions();

    /**
     *	Specify the columns that can be dynamically filtered from the query string.
     *	Columns can be referenced directly - array('column1', 'column2') - or they
     *	can be aliased - array('co1' => 'column1', 'co2' => 'column2').
     *
     *	@param array $columns The columns to use
     *  @param bool $append Append or overwrite exisiting values
     *
     *	@return $this
     */
    public function setColumns($columns, $append = true);


    /**
     *
     * Parse the query string
     *
     * @param array $str The query string
     * @param bool $append Append or overwrite the existing query string data
     * @param bool $default Default to $_SERVER['QUERY_STRING'] if $str isn't given
     * @return $this
     */
    public function setQuerystring(array $str = array(), $append = true, $default = true);


    /**
     * Laravel Eloquent query scope.
     *
     * @param $query \Illuminate\Database\Eloquent query object
     * @param array $columns
     * @param bool $validate
     * @return \Illuminate\Database\Eloquent object
     */
    public function scopeFilterColumns($query, $columns = array(), $validate = false);

    /**
     *
     *	Validate the specified columns against the table's actual columns.
     *
     *	@return $this
     */
    public function validateColumns();
}