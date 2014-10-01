<?php namespace Heroicpixels\Filterable;
/**
 *	This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 *	@license http://opensource.org/licenses/MIT MIT
 */
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 *	A trait for dynamically filtering Eloquent models based on query string paremeters.
 *
 *	@package Filterable
 */
abstract class Filterable extends Eloquent {
	/**
	 *	Data structures and default values
	 *
	 *	@var array
	 */
	public $f;
	/**
	 *	Setup data structures and default values
	 */
	public function resetFilterableOptions()
	{
		$this->f = array('bools'			=> array('and' => 'where', 'or' => 'orWhere'),
						 'callbacks'		=> array(),
					  	 'columns' 			=> array(), 
					 	 'defaultOperator'	=> '=',
						 'defaultWhere'		=> 'where',
					  	 'filters'			=> array(),
						 'orderby'			=> 'orderby',
						 'order'			=> 'order',
					  	 'operators'		=> array('=', '<', '>', '!=', 'like'),
					  	 'qstring' 			=> array());	
		return $this;
	}
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
	public function setColumns($columns, $append = true)
	{
		if ( is_null($this->f) ) {
			$this->resetFilterableOptions();	
		}
		if ( count(array_filter(array_keys($columns), 'is_string')) == 0 ) {
			// Numeric indexes, so build new associative index array
			$columns = array_combine($columns, $columns);
		}
		if ( !$append ) {
			// Overwrite data
			$this->f['columns'] = array();	
		}

		foreach ( $columns as $k => $v ) {
			// Strip off callbacks
			if ( is_callable($v) ) {
				$this->f['callbacks'][] = $v;
				unset($columns[$k]);	
			}
		}
		$this->f['columns'] = array_merge($this->f['columns'], $columns);
		return $this;
	}
	/**
	 *	Parse the query string
	 *
	 *	@param $str array The query string
	 *	@param $append Append or overwrite existing query string data
	 *	@param $default Default to $_SERVER['QUERY_STRING'] if $str isn't given
	 *
	 *	@return $this
	 */
	public function setQuerystring(array $str = array(), $append = true, $default = true)
	{
		if ( is_null($this->f) ) {
			$this->resetFilterableOptions();	
		}
		if ( sizeof($str) == 0 && $default ) {
			// Default to PHP query string
			parse_str($_SERVER['QUERY_STRING'], $this->f['qstring']);
		} else {
			$this->f['qstring'] = $str;	
		}
		if ( sizeof($this->f['qstring']) > 0 ) {
			if ( !$append ) {
				// Overwrite data
				$this->f['filters'] = array();
			}
			foreach ( $this->f['qstring'] as $k => $v ) {
				if ( $v == '' ) {
					continue;
				}
				$thisColumn = isset($this->f['columns'][$k]) ? $this->f['columns'][$k] : false;
				if ( $thisColumn ) {
					// Query string part matches column (or alias)
					$this->f['filters'][$thisColumn]['val'] = $v;
					// Evaluate boolean parameter in query string
					$thisBoolData = isset($this->f['qstring']['bool'][$k]) ? $this->f['qstring']['bool'][$k] : false;
					$thisBoolAvailable = $thisBoolData && isset($this->f['bools'][$thisBoolData]) ? $this->f['bools'][$thisBoolData] : false;
					if ( $thisBoolData && $thisBoolAvailable ) {
						$this->f['filters'][$thisColumn]['boolean'] = $thisBoolAvailable;
					} else {
						$this->f['filters'][$thisColumn]['boolean'] = $this->f['defaultWhere'];
					}
					// Evaluate operator parameters in the query string
					if ( isset($this->f['qstring']['operator'][$k]) && in_array($this->f['qstring']['operator'][$k], $this->f['operators']) ) {
						$this->f['filters'][$thisColumn]['operator'] = $this->f['qstring']['operator'][$k];
					} else {
						// Default operator
						$this->f['filters'][$thisColumn]['operator'] = $this->f['defaultOperator'];
					}
				}
			}
		}
		return $this;
	}
	/**
	 *	Laravel Eloquent query scope.
	 *
	 *	@param $query Eloquent query object
	 *	@return Eloquent query object
	 */
	public function scopeFilterColumns($query, $columns = array(), $validate = false)
	{
		if ( sizeof($columns) > 0 ) {
			// Set columns that can be filtered
			$this->setColumns($columns);
		}
		// Validate columns
		if ( $validate ) {
			$this->validateColumns();	
		}
		// Ensure that query string is parsed at least once
		if ( sizeof($this->f['filters']) == 0 ) {
			$this->setQuerystring();
		}
		// Apply conditions to Eloquent query object
		if ( sizeof($this->f['filters']) > 0 ) {
			foreach ( $this->f['filters'] as $k => $v ) {
				$where = $v['boolean'];	
				if ( is_array($v['val']) ) {
					if ( isset($v['val']['start']) && isset($v['val']['end']) ) {
						// BETWEEN a AND b
						$query->whereBetween($k, array($v['val']['start'], $v['val']['end']));
					} else {
						// a = b OR c = d OR...
						$query->{$where}(function($q) use ($k, $v, $query)
						{
							foreach ( $v['val'] as $key => $val ) {
								$q->orWhere($k, $v['operator'], $val);	
							}	
						});
					}
				} else {
					// a = b
					$query->{$where}($k, $v['operator'], $v['val']);
				}
			}
		}
		// Apply callbacks
		if ( sizeof($this->f['callbacks']) > 0 ) {
			foreach ( $this->f['callbacks'] as $v ) {
				$v($query);	
			}
		}
		// Sorting
		if ( isset($this->f['qstring'][$this->f['orderby']]) && isset($this->f['columns'][$this->f['qstring'][$this->f['orderby']]]) ) {
			$order = isset($this->f['qstring'][$this->f['order']]) ? $this->f['qstring'][$this->f['order']] : 'asc';
			$query->orderBy($this->f['columns'][$this->f['qstring'][$this->f['orderby']]], $order);
		}
		return $query;
	}
	/**
	 *
	 *	Validate the specified columns against the table's actual columns.	
	 *
	 *	@return $this
	 */
	public function validateColumns()
	{	
		if ( class_exists('\\Doctrine\\DBAL\\Driver\\PDOMySql\\Driver') ) {
			$columns = DB::connection()->getDoctrineSchemaManager()->listTableColumns($this->getTable());
			foreach ( $columns as $column ) {
				$name = $column->getName();
				$columns[$name] = $name;
			}
			$this->f['columns'] = array_intersect($this->f['columns'], $columns);
			return $this;
		}
		die('You must have Doctrine installed in order to validate columns');
	}
}
