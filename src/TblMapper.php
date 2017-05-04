<?php

namespace hoksi;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Codeigniter4 Query builder Mapper
 *
 * @author hoksi(hoksi2k@hanmail.net)
 */
class TblMapper {

    /**
     * Read Only Database
     * CodeIgniter DataBase object
     * 
     * @var objcet
     */
    protected $rodb;

    /**
     * Read Write Database
     * CodeIgniter DataBase object
     * 
     * @var object
     */
    protected $rwdb;

    /**
     * Codeigniter Frmework
     * 
     * @var object
     */
    protected $ci;

    /**
     * Database Table name
     * 
     * @var type 
     */
    protected $tableName;

    /**
     * Excute Last query
     * 
     * @var string
     */
    protected $lastQuery;

    /**
     * Return Result Type
     * 
     * @var string 
     */
    protected $resultType = '';

    /**
     * Limit
     * 
     * @var array
     */
    protected $_limit = false;

    /**
     * Offset
     * 
     * @var array
     */
    protected $_offset = false;

    /**
     * Select data
     * 
     * @var array
     */
    protected $_select = array();

    /**
     * Join data
     * 
     * @var array
     */
    protected $_join = array();

    /**
     * Where data
     * 
     * @var array
     */
    protected $_where = array();

    /**
     * Group by data
     * 
     * @var array
     */
    protected $_groupBy = array();

    /**
     * Having data
     * 
     * @var array
     */
    protected $_having = array();

    /**
     * Order by data
     * 
     * @var array
     */
    public $_orderBy = array();

    /**
     * set data
     * 
     * @var array
     */
    protected $_set = array();

    /**
     * count
     * 
     * @var array
     */
    protected $_count = false;

    /**
     * Auto reset mode
     * 
     * @var array
     */
    protected $_autoReset = true;

    /**
     * Constructor
     * 
     * @param string $tableName
     */
    public function __construct($tableName = null) {
        $this->ci = & get_instance();

        if (isset($this->ci->db) && is_object($this->ci->db)) {
            $this->rodb = & $this->ci->db;
            $this->rwdb = & $this->ci->db;
        } else {
            throw new \Exception('Database Not Connected!');
        }

        if ($tableName != null) {
            $this->table($tableName);
        }
    }

    /**
     * Set resultType
     * 
     * @param string $resultType
     * @return $this
     */
    public function setResultType($resultType) {
        $this->resultType = ($resultType == 'array' ? 'array' : $resultType );

        return $this;
    }

    /**
     * Returns an instance of the query builder for this connection.
     * 
     * @param type $tableName
     * @return TblMapper
     */
    public function table($tableName) {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Return table Name
     * 
     * @return string
     */
    public function getTable() {
        return $this->tableName;
    }

    /**
     * Returns the last query's statement object.
     *
     * @return mixed
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }

    /**
     * "Smart" Escaping
     *
     * Escapes data based on type.
     * Sets boolean and null types.
     *
     * @param $str
     *
     * @return mixed
     */
    public function escape($str) {
        return $this->rodb->escape($str);
    }

    /**
     * Orchestrates a query against the database. Queries must use
     * Database\Statement objects to store the query and build it.
     * This method works with the cache.
     *
     * Should automatically handle different connections for read/write
     * queries if needed.
     *
     * @param string $sql
     * @param array  ...$binds
     *
     * @return mixed
     */
    public function query($sql, $binds = null) {
        $this->lastQuery = $sql;

        switch (strtoupper(substr(ltrim($sql), 0, 6))) {
            case 'SELECT':
                $query = $this->rodb->query($sql, $binds);
                return $this->resultType == 'array' ? $query->result_array() : $query->result($this->resultType);

            case 'INSERT':
                $this->wrdb->query($sql, $binds);
                return $this->wrdb->insert_id();

            case 'DELETE':
            case 'UPDATE':
                $this->wrdb->query($sql, $binds);
                return $this->wrdb->affected_rows();
        }
    }

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     *
     * @param    string    the limit clause
     * @param    string    the offset clause
     *
     * @return    mixed
     */
    public function get($limit = null, $offset = null) {
        // Limit
        if ($limit !== null) {
            $this->limit($limit, $offset);
        }

        // Select column compile
        $this->compileSelect();

        $query = $this->rodb->get();
        $this->lastQuery = $this->rodb->last_query();
        $this->resetSelect();

        return $this->resultType == 'array' ? $query->result_array() : $query->result($this->resultType);
    }

    /**
     * Get_Where
     *
     * Allows the where clause, limit and offset to be added directly
     *
     * @param    string $where
     * @param    int    $limit
     * @param    int    $offset
     *
     * @return    ResultInterface
     */
    public function getWhere($where = null, $limit = null, $offset = null) {
        if ($where !== null) {
            $this->where($where);
        }
        
        return $this->get($limit, $offset);
    }

    /**
     * Get one row
     * 
     * @return mixed
     */
    public function getOne() {
        $row = $this->get(1);

        return isset($row[0]) ? $row[0] : false;
    }

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     *
     * @param    string    the limit clause
     * @param    string    the offset clause
     *
     * @return    mixed
     */
    public function getCount() {
        $isAll = !empty($this->_where) && !empty($this->_groupBy);

        // Row count mode
        $this->_count = true;

        // Select column compile
        $this->compileSelect();

        $rowCnt = $isAll ? $this->rodb->count_all() : $this->rodb->count_all_results();
        $this->lastQuery = $this->rodb->last_query();
        $this->resetSelect();

        return $rowCnt;
    }

    /**
     * Get SELECT query string
     *
     * Compiles a SELECT query string and returns the sql.
     *
     * @param    bool      TRUE: resets QB values; FALSE: leave QB values alone
     *
     * @return    string
     */
    public function getCompiledSelect($reset = true) {
        // Select column compile
        $this->compileSelect()->resetSelect();

        return $this->rodb->get_compiled_select('', $reset);
    }

    /**
     * From
     *
     * Generates the FROM portion of the query
     *
     * @param    mixed $from      can be a string or array
     *
     * @return    TblMapper
     */
    public function from($from) {
        return $this->table($from);
    }

    /**
     * Select
     *
     * Generates the SELECT portion of the query
     *
     * @param	string
     * @param	mixed
     * @return	TblMapper
     */
    public function select($select = '*', $escape = NULL) {
        $this->_select[] = array('select' => $select, 'escape' => $escape, 'type' => 'select');

        return $this;
    }

    /**
     * Select Max
     *
     * Generates a SELECT MAX(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     *
     * @return    TblMapper
     */
    public function selectMax($select = '', $alias = '') {
        $this->_select[] = array('select' => $select, 'alias' => $alias, 'type' => 'max');

        return $this;
    }

    /**
     * Select Min
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     *
     * @return    TblMapper
     */
    public function selectMin($select = '', $alias = '') {
        $this->_select[] = array('select' => $select, 'alias' => $alias, 'type' => 'min');

        return $this;
    }

    /**
     * Select Average
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     *
     * @return    TblMapper
     */
    public function selectAvg($select = '', $alias = '') {
        $this->_select[] = array('select' => $select, 'alias' => $alias, 'type' => 'avg');

        return $this;
    }

    /**
     * Select Sum
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     *
     * @return    TblMapper
     */
    public function selectSum($select = '', $alias = '') {
        $this->_select[] = array('select' => $select, 'alias' => $alias, 'type' => 'sum');

        return $this;
    }

    /**
     * JOIN
     *
     * Generates the JOIN portion of the query
     *
     * @param    string
     * @param    string    the join condition
     * @param    string    the type of join
     * @param    string    whether not to try to escape identifiers
     *
     * @return    TblMapper
     */
    public function join($table, $cond, $type = '', $escape = null) {
        $this->_join[] = array('table' => $table, 'cond' => $cond, 'type' => $type, 'escape' => $escape);

        return $this;
    }

    /**
     * WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed
     * @param    mixed
     * @param    bool
     *
     * @return    TblMapper
     */
    public function where($key, $value = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $value, 'escape' => $escape, 'type' => 'where');

        return $this;
    }

    /**
     * OR WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed
     * @param    mixed
     * @param    bool
     *
     * @return    TblMapper
     */
    public function orWhere($key, $value = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $value, 'escape' => $escape, 'type' => 'orWhere');

        return $this;
    }

    /**
     * WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param    string $key    The field to search
     * @param    array  $values The values searched on
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function whereIn($key = null, $values = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $values, 'escape' => $escape, 'type' => 'whereIn');

        return $this;
    }

    /**
     * OR WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param    string $key    The field to search
     * @param    array  $values The values searched on
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function orWhereIn($key = null, $values = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $values, 'escape' => $escape, 'type' => 'orWhereIn');

        return $this;
    }

    /**
     * WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param    string $key    The field to search
     * @param    array  $values The values searched on
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function whereNotIn($key = null, $values = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $values, 'escape' => $escape, 'type' => 'whereNotIn');

        return $this;
    }

    /**
     * OR WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param    string $key    The field to search
     * @param    array  $values The values searched on
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function orWhereNotIn($key = null, $values = null, $escape = null) {
        $this->_where[] = array('key' => $key, 'value' => $values, 'escape' => $escape, 'type' => 'orWhereNotIn');

        return $this;
    }

    /**
     * LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed  $field
     * @param    string $match
     * @param    string $side
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function like($field, $match = '', $side = 'both', $escape = null) {
        $this->_where[] = array('field' => $field, 'match' => $match, 'side' => $side, 'escape' => $escape, 'type' => 'like');

        return $this;
    }

    /**
     * OR LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed  $field
     * @param    string $match
     * @param    string $side
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function orLike($field, $match = '', $side = 'both', $escape = null) {
        $this->_where[] = array('field' => $field, 'match' => $match, 'side' => $side, 'escape' => $escape, 'type' => 'orLike');

        return $this;
    }

    /**
     * NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed  $field
     * @param    string $match
     * @param    string $side
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function notLike($field, $match = '', $side = 'both', $escape = null) {
        $this->_where[] = array('field' => $field, 'match' => $match, 'side' => $side, 'escape' => $escape, 'type' => 'notLike');

        return $this;
    }

    /**
     * OR NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed  $field
     * @param    string $match
     * @param    string $side
     * @param    bool   $escape
     * @param    bool   $insensitiveSearch	IF true, will force a case-insensitive search
     *
     * @return    TblMapper
     */
    public function orNotLike($field, $match = '', $side = 'both', $escape = null) {
        $this->_where[] = array('field' => $field, 'match' => $match, 'side' => $side, 'escape' => $escape, 'type' => 'orNotLike');

        return $this;
    }

    /**
     * GROUP BY
     *
     * @param    string $by
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function groupBy($by, $escape = null) {
        $this->_groupBy[] = array('by' => $by, 'escape' => $escape);

        return $this;
    }

    /**
     * DISTINCT
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @param    bool $val
     *
     * @return    TblMapper
     */
    public function distinct($val = true) {
        $this->rodb->distinct($val);

        return $this;
    }

    /**
     * HAVING
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $key
     * @param    string $value
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function having($key, $value = null, $escape = null) {
        $this->_having[] = array('key' => $key, 'value' => $value, 'escape' => $escape, 'type' => 'having');

        return $this;
    }

    /**
     * OR HAVING
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $key
     * @param    string $value
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function orHaving($key, $value = null, $escape = null) {
        $this->_having[] = array('key' => $key, 'value' => $value, 'escape' => $escape, 'type' => 'orHaving');

        return $this;
    }

    /**
     * ORDER BY
     *
     * @param    string $orderby
     * @param    string $direction ASC, DESC or RANDOM
     * @param    bool   $escape
     *
     * @return    TblMapper
     */
    public function orderBy($orderby, $direction = '', $escape = null) {
        $this->_orderBy[] = array('orderby' => $orderby, 'direction' => $direction, 'escape' => $escape);

        return $this;
    }

    /**
     * LIMIT
     *
     * @param    int $value  LIMIT value
     * @param    int $offset OFFSET value
     *
     * @return    TblMapper
     */
    public function limit($value, $offset = 0) {
        $this->_limit = $value;
        $this->_offset = $offset;

        return $this;
    }

    /**
     * Starts a query group.
     *
     * @param    string $not  (Internal use only)
     * @param    string $groupType (Internal use only)
     *
     * @return    TblMapper
     */
    public function groupStart($not = '', $groupType = 'AND ') {
        $this->_where[] = array('not' => $not, 'groupType' => $groupType, 'type' => 'groupStart');

        return $this;
    }

    /**
     * Starts a query group, but ORs the group
     *
     * @return    TblMapper
     */
    public function orGroupStart() {
        return $this->groupStart('', 'OR ');
    }

    /**
     * Starts a query group, but NOTs the group
     *
     * @return    TblMapper
     */
    public function notGroupStart() {
        return $this->groupStart('NOT ', 'AND ');
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group, but OR NOTs the group
     *
     * @return    TblMapper
     */
    public function orNotGroupStart() {
        return $this->groupStart('NOT ', 'OR ');
    }

    /**
     * Ends a query group
     *
     * @return    TblMapper
     */
    public function groupEnd() {
        $this->_where[] = array('type' => 'groupEnd');

        return $this;
    }

    /**
     * The "set" function.
     *
     * Allows key/value pairs to be set for insert(), update() or replace().
     *
     * @param    string|array $key    Field name, or an array of field/value pairs
     * @param    string       $value  Field value, if $key is a single field
     * @param    bool                 Whether to escape values and identifiers
     *
     * @return    TblMapper
     */
    public function set($key, $value = '', $escape = null) {
        $this->_set[] = array('key' => $key, 'value' => $value, 'escape' => $escape);

        return $this;
    }

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param         array     an associative array of insert values
     * @param    bool $escape   Whether to escape values and identifiers
     *
     * @return    int last insert ID
     */
    public function insert($set = null, $escape = null) {
        $this->compileSet()->resetWrite();

        $this->rwdb->insert($this->tableName, $set, $escape);
        $this->lastQuery = $this->rwdb->last_query();

        return $this->rwdb->insert_id();
    }

    /**
     * Get INSERT query string
     *
     * Compiles an insert query and returns the sql
     *
     * @param    bool      TRUE: reset QB values; FALSE: leave QB values alone
     *
     * @return    string
     */
    public function getCompiledInsert($reset = true) {
        $this->compileSet()->resetWrite();

        return $this->rwdb->get_compiled_insert($this->tableName, $reset);
    }

    /**
     * Insert_Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @param    array $set    An associative array of insert values
     * @param    bool  $escape Whether to escape values and identifiers
     *
     * @param int      $batch_size
     * @param bool     $testing
     *
     * @return int Number of rows inserted or FALSE on failure
     */
    public function insertBatch($set = null, $escape = null, $batch_size = 100) {
        $this->compileSet()->resetWrite();
        $ret = $this->rwdb->insert_batch($this->tableName, $set, $escape, $batch_size);
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Replace
     *
     * Compiles an replace into string and runs the query
     *
     * @param      array     an associative array of insert values
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function replace($set = null) {
        $this->compileSet()->resetWrite();
        $ret = $this->rwdb->replace($this->tableName, $set);
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * UPDATE
     *
     * Compiles an update string and runs the query.
     *
     * @param    array $set  An associative array of update values
     * @param    mixed $where
     * @param    int   $limit
     *
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function update($set = null, $where = null, $limit = null) {
        $this->compileSet()->compileWhere();
        $ret = $this->rwdb->update($this->tableName, $set, $where, ($limit == null ? $this->_limit : $limit));
        $this->resetWrite();
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Update_Batch
     *
     * Compiles an update string and runs the query
     *
     * @param    array     an associative array of update values
     * @param    string    the where key
     * @param    int       The size of the batch to run
     *
     * @return    int    number of rows affected or FALSE on failure
     */
    public function updateBatch($set = null, $index = null, $batch_size = 100) {
        $this->compileSet()->compileWhere();
        $ret = $this->rwdb->update_batch($this->tableName, $set, $index, $batch_size);
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Get UPDATE query string
     *
     * Compiles an update query and returns the sql
     *
     * @param    bool      TRUE: reset QB values; FALSE: leave QB values alone
     *
     * @return    string
     */
    public function getCompiledUpdate($reset = true) {
        $this->compileSet()->compileWhere();

        return $this->rwdb->get_compiled_update($this->tableName, $reset);
    }

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @param    mixed $where    the where clause
     * @param    mixed $limit    the limit clause
     * @param    bool  $reset_data
     *
     * @return    mixed
     */
    public function delete($where = '', $limit = null, $reset_data = true) {
        $this->compileWhere();
        $ret = $this->rwdb->delete($this->tableName, $where, ($limit == null ? $this->_limit : $limit), $reset_data);
        $this->resetWrite();
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Empty Table
     *
     * Compiles a delete string and runs "DELETE FROM table"
     *
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function emptyTable() {
        $ret = $this->rwdb->empty_table($this->tableName);
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function truncate() {
        $ret = $this->rwdb->truncate($this->tableName);
        $this->lastQuery = $this->rwdb->last_query();

        return $ret;
    }

    /**
     * Get DELETE query string
     *
     * Compiles a delete query string and returns the sql
     *
     * @param    string    the table to delete from
     * @param    bool      TRUE: reset QB values; FALSE: leave QB values alone
     *
     * @return    string
     */
    public function getCompiledDelete($reset = true) {
        $this->compileWhere()->resetWrite();
        return $this->rodb->get_compiled_delete($this->tableName, $reset);
    }

    /**
     * Compile SELECT column
     * 
     * @return TblMapper
     */
    protected function compileSelect() {
        // Select column
        if ($this->_count === false && !empty($this->_select)) {
            foreach ($this->_select as $select) {
                switch ($select['type']) {
                    case 'select':
                        $this->rodb->select($select['select'], $select['escape']);
                        break;
                    case 'max':
                        $this->rodb->select_max($select['select'], $select['alias']);
                        break;
                    case 'min':
                        $this->rodb->select_min($select['select'], $select['alias']);
                        break;
                    case 'avg':
                        $this->rodb->select_avg($select['select'], $select['alias']);
                        break;
                    case 'sum':
                        $this->rodb->select_sum($select['select'], $select['alias']);
                        break;
                }
            }
        }

        // From table
        $this->rodb->from($this->tableName);

        // Join table
        if (!empty($this->_join)) {
            foreach ($this->_join as $join) {
                $this->rodb->join($join['table'], $join['cond'], $join['type'], $join['escape']);
            }
        }

        // Where
        $this->compileWhere();

        // Group By
        if (!empty($this->_groupBy)) {
            foreach ($this->_groupBy as $groupBy) {
                $this->rodb->group_by($groupBy['by'], $groupBy['escape']);
            }
        }

        // Having
        if (!empty($this->_having)) {
            foreach ($this->_having as $having) {
                switch ($having['type']) {
                    case 'having':
                        $this->rodb->having($having['key'], $having['value'], $having['escape']);
                        break;
                    case 'orHaving':
                        $this->rodb->or_having($having['key'], $having['value'], $having['escape']);
                        break;
                }
            }
        }

        // Order By
        if (!empty($this->_orderBy)) {
            foreach ($this->_orderBy as $orderBy) {
                $this->rodb->order_by($orderBy['orderby'], $orderBy['direction'], $orderBy['escape']);
            }
        }

        // Limit
        $this->rodb->limit($this->_limit, $this->_offset);

        return $this;
    }

    /**
     * Compile WHERE
     * 
     * @return TblMapper
     */
    protected function compileWhere() {
        if (!empty($this->_where)) {
            foreach ($this->_where as $where) {
                switch ($where['type']) {
                    case 'where':
                        $this->rodb->where($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'orWhere':
                        $this->rodb->or_where($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'whereIn':
                        $this->rodb->where_in($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'orWhereIn':
                        $this->rodb->or_where_in($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'whereNotIn':
                        $this->rodb->where_not_in($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'orWhereNotIn':
                        $this->rodb->or_where_not_in($where['key'], $where['value'], $where['escape']);
                        break;
                    case 'like':
                        $this->rodb->like($where['field'], $where['match'], $where['side'], $where['escape']);
                        break;
                    case 'orLike':
                        $this->rodb->like($where['field'], $where['match'], $where['side'], $where['escape']);
                        break;
                    case 'notLike':
                        $this->rodb->not_like($where['field'], $where['match'], $where['side'], $where['escape']);
                        break;
                    case 'orNotLike':
                        $this->rodb->or_not_like($where['field'], $where['match'], $where['side'], $where['escape']);
                        break;
                    case 'groupStart':
                        $this->rodb->group_start($where['not'], $where['groupType']);
                        break;
                    case 'groupEnd':
                        $this->rodb->group_end();
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Compile SET
     * 
     * @return TblMapper
     */
    protected function compileSet() {
        if (!empty($this->_set)) {
            foreach ($this->_set as $set) {
                $this->rwdb->set($set['key'], $set['value'], $set['escape']);
            }
        }

        return $this;
    }

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @param    array    An array of fields to reset
     *
     * @return    void
     */
    protected function resetRun($qb_reset_items) {
        foreach ($qb_reset_items as $item => $default_value) {
            $this->$item = $default_value;
        }
    }

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @return    void
     */
    protected function resetSelect() {
        if ($this->_autoReset === true) {
            $this->resetRun([
                '_select' => array(),
                '_join' => array(),
                '_where' => array(),
                '_groupBy' => array(),
                '_having' => array(),
                '_orderBy' => array(),
                '_limit' => false,
                '_offset' => false,
            ]);
        }
    }

    /**
     * Resets the query builder "write" values.
     *
     * Called by the insert() update() insertBatch() updateBatch() and delete() functions
     *
     * @return    void
     */
    protected function resetWrite() {
        $this->resetRun([
            '_set' => [],
            '_join' => [],
            '_where' => [],
            '_orderBy' => [],
            '_limit' => false,
            '_offset' => false,
        ]);
    }

    /**
     * Start Transaction
     *
     * @param	bool	$test_mode = FALSE
     * @return	bool
     */
    public function transStart($test_mode = false) {
        return $this->rwdb->trans_start($test_mode);
    }

    /**
     * Complete Transaction
     *
     * @return	bool
     */
    public function transComplete() {
        return $this->rwdb->trans_complete();
    }

    /**
     * Enable/disable Transaction Strict Mode
     *
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all subsequent groups will be
     * rolled back.
     *
     * If strict mode is disabled, each group is treated autonomously,
     * meaning a failure of one group will not affect any others
     *
     * @param    bool $mode = true
     *
     * @return $this
     */
    public function transStrict($mode = true) {
        $this->rwdb->trans_strict($mode);

        return $this;
    }

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     *
     * @return	bool
     */
    public function transStatus() {
        return $this->rwdb->trans_status();
    }

    /**
     * Disable Transactions
     *
     * This permits transactions to be disabled at run-time.
     */
    public function transOff() {
        $this->rwdb->trans_off();
    }

    /**
     * Begin Transaction
     *
     * @param	bool	$test_mode
     * @return	bool
     */
    public function transBegin($test_mode = false) {
        return $this->rwdb->trans_begin($test_mode);
    }

    /**
     * Commit Transaction
     *
     * @return	bool
     */
    public function transCommit() {
        return $this->rwdb->trans_commit();
    }

}
