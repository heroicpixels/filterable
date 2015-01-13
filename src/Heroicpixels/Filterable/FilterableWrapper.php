<?php namespace Heroicpixels\Filterable;
/**
 *	This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 *	@license http://opensource.org/licenses/MIT MIT
 */
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class FilterableWrapper {
	/**
	 *	DB or Eloquent model
	 */
	public $model;
	/**
	 *	Data structures and default values
	 *
	 *	@var array
	 */
	public $filterable;
		
	public function __construct($model) {
		$this->model = $model;
	}
	
	/**
	 *	Setup data structures and default values
	 */
	public function resetFilterableOptions() {
		$this->filterable = array(
			'bools'				=> array('and' => 'where', 'or' => 'orWhere'),
			'callbacks'			=> array(),
			'columns' 			=> array(), 
			'defaultOperator'	=> '=',
			'defaultWhere'		=> 'where',
			'filters'			=> array(),
			'orderby'			=> 'orderby',
			'order'				=> 'order',
			'operators'			=> array('=', '<', '>', '!=', 'like'),
			'qstring' 			=> array()
		);	
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
	public function setColumns($columns, $append = true) {
		if ( is_null($this->filterable) ) {
			$this->resetFilterableOptions();	
		}
		if ( count(array_filter(array_keys($columns), 'is_string')) == 0 ) {
			// Numeric indexes, so build new associative index array
			$columns = array_combine($columns, $columns);
		}
		if ( !$append ) {
			// Overwrite data
			$this->filterable['columns'] = array();	
		}
		foreach ( $columns as $k => $v ) {
			// Strip off callbacks
			if ( is_callable($v) ) {
				$this->filterable['callbacks'][] = $v;
				unset($columns[$k]);	
			}
		}
		$this->filterable['columns'] = array_merge($this->filterable['columns'], $columns);
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
	public function setQuerystring(array $str = array(), $append = true, $default = true) {
		if ( is_null($this->filterable) ) {
			$this->resetFilterableOptions();	
		}
		if ( sizeof($str) == 0 && $default ) {
			// Default to PHP query string
			parse_str($_SERVER['QUERY_STRING'], $this->filterable['qstring']);
		} else {
			$this->filterable['qstring'] = $str;	
		}
		if ( sizeof($this->filterable['qstring']) > 0 ) {
			if ( !$append ) {
				// Overwrite data
				$this->filterable['filters'] = array();
			}
			foreach ( $this->filterable['qstring'] as $k => $v ) {
				if ( $v == '' ) {
					continue;
				}
				$thisColumn = isset($this->filterable['columns'][$k]) ? $this->filterable['columns'][$k] : false;
				if ( $thisColumn ) {
					// Query string part matches column (or alias)
					$this->filterable['filters'][$thisColumn]['val'] = $v;
					// Evaluate boolean parameter in query string
					$thisBoolData = isset($this->filterable['qstring']['bool'][$k]) ? $this->filterable['qstring']['bool'][$k] : false;
					$thisBoolAvailable = $thisBoolData && isset($this->filterable['bools'][$thisBoolData]) ? $this->filterable['bools'][$thisBoolData] : false;
					if ( $thisBoolData && $thisBoolAvailable ) {
						$this->filterable['filters'][$thisColumn]['boolean'] = $thisBoolAvailable;
					} else {
						$this->filterable['filters'][$thisColumn]['boolean'] = $this->filterable['defaultWhere'];
					}
					// Evaluate operator parameters in the query string
					if ( isset($this->filterable['qstring']['operator'][$k]) && in_array($this->filterable['qstring']['operator'][$k], $this->filterable['operators']) ) {
						$this->filterable['filters'][$thisColumn]['operator'] = $this->filterable['qstring']['operator'][$k];
					} else {
						// Default operator
						$this->filterable['filters'][$thisColumn]['operator'] = $this->filterable['defaultOperator'];
					}
				}
			}
		}
		return $this;
	}
	/**
	 *	Apply filters to DB/Eloquent object
	 *
	 *	@param $query Eloquent query object
	 *	@return Eloquent query object
	 */
	public function filterColumns($columns = array(), $validate = false) {
		if ( sizeof($columns) > 0 ) {
			// Set columns that can be filtered
			$this->setColumns($columns);
		}
		// Validate columns
		if ( $validate ) {
			$this->validateColumns();	
		}
		// Ensure that query string is parsed at least once
		if ( sizeof($this->filterable['filters']) == 0 ) {
			$this->setQuerystring();
		}
		// Apply conditions to Eloquent query object
		if ( sizeof($this->filterable['filters']) > 0 ) {
			foreach ( $this->filterable['filters'] as $k => $v ) {
				$where = $v['boolean'];	
				if ( is_array($v['val']) ) {
					if ( isset($v['val']['start']) && isset($v['val']['end']) ) {
						// BETWEEN a AND b
						$this->model->whereBetween($k, array($v['val']['start'], $v['val']['end']));
					} else {
						// a = b OR c = d OR...
						$this->model->{$where}(function($q) use ($k, $v)
						{
							foreach ( $v['val'] as $key => $val ) {
								$q->orWhere($k, $v['operator'], $val);	
							}	
						});
					}
				} else {
					// a = b
					$this->model->{$where}($k, $v['operator'], $v['val']);
				}
			}
		}
		// Apply callbacks
		if ( sizeof($this->filterable['callbacks']) > 0 ) {
			foreach ( $this->filterable['callbacks'] as $v ) {
				$v($this->model);	
			}
		}
		// Sorting
		if ( isset($this->filterable['qstring'][$this->filterable['orderby']]) && isset($this->filterable['columns'][$this->filterable['qstring'][$this->filterable['orderby']]]) ) {
			$order = isset($this->filterable['qstring'][$this->filterable['order']]) ? $this->filterable['qstring'][$this->filterable['order']] : 'asc';
			$this->model->orderBy($this->filterable['columns'][$this->filterable['qstring'][$this->filterable['orderby']]], $order);
		}
		return $this->model;
	}
	/**
	 *
	 *	Validate the specified columns against the table's actual columns.	
	 *
	 *	@return $this
	 */
	public function validateColumns() {	
		if ( class_exists('\\Doctrine\\DBAL\\Driver\\PDOMySql\\Driver') ) {
			$columns = DB::connection()->getDoctrineSchemaManager()->listTableColumns($this->getTable());
			foreach ( $columns as $column ) {
				$name = $column->getName();
				$columns[$name] = $name;
			}
			$this->filterable['columns'] = array_intersect($this->filterable['columns'], $columns);
			return $this;
		}
		die('You must have Doctrine installed in order to validate columns');
	}
	/**
	 *	Pass methods to the DB/Eloquent object
	 */
	public function __call($name, $arguments) {
		$retrieval = in_array($name, array('all', 'find', 'findOrFail', 'first', 'get', 'lists', 'paginate'));
		$this->model = call_user_func_array(array($this->model, $name), $arguments);
		if ( $retrieval ) {
			return $this->model;
		} else {
			return $this;
		}
	}
}
