<?php

namespace hoksi;

use hoksi;

/**
 * Model
 *
 * @author Hoksi (hoksi2k@hanmail.net)
 */
class Model {

    /**
     * Name of database table
     *
     * @var string
     */
    protected $table;

    /**
     * Query Builder object
     *
     * @var BaseBuilder
     */
    protected $builder;

    /**
     * The table's primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The format that the results should be returned as.
     * Will be overridden if the as* methods are used.
     *
     * @var string
     */
    protected $returnType = 'array';

    /**
     * If this model should use "softDeletes" and
     * simply set a flag when rows are deleted, or
     * do hard deletes.
     *
     * @var bool
     */
    protected $useSoftDeletes = false;

    /**
     * Used by asArray and asObject to provide
     * temporary overrides of model default.
     *
     * @var string
     */
    protected $tempReturnType;

    /**
     * Used by withDeleted to override the
     * model's softDelete setting.
     *
     * @var bool
     */
    protected $tempUseSoftDeletes;

    /**
     * Skip the model's validation. Used in conjunction with skipValidation()
     * to skip data validation for any future calls.
     *
     * @var bool
     */
    protected $skipValidation = false;

    /**
     * Our validator instance.
     *
     * @var \CodeIgniter\Validation\ValidationInterface
     */
    protected $validation;

    /**
     * Rules used to validate data in insert, update, and save methods.
     * The array must match the format of data passed to the Validation
     * library.
     *
     * @var array
     */
    protected $validationRules = array();

    /**
     * Whether we should limit fields in inserts
     * and updates to those available in $allowedFields or not.
     *
     * @var bool
     */
    protected $protectFields = true;

    /**
     * An array of field names that are allowed
     * to be set by the user in inserts/updates.
     *
     * @var array
     */
    protected $allowedFields = array('name');

    /**
     * If true, will set created_at, and updated_at
     * values during insert and update routines.
     *
     * @var bool
     */
    protected $useTimestamps = false;

    /**
     * The column used for insert timestampes
     *
     * @var string
     */
    protected $createdField = 'created_at';

    /**
     * The column used for update timestamps
     *
     * @var string
     */
    protected $updatedField = 'updated_at';

    /**
     * The type of column that created_at and updated_at
     * are expected to.
     *
     * Allowed: 'datetime', 'date', 'int'
     *
     * @var string
     */
    protected $dateFormat = 'datetime';

    /**
     * Salt
     * 
     * @var string
     */
    protected $salt = '';

    /**
     * Specify the table associated with a model
     *
     * @param string $table
     *
     * @return $this
     */
    public function setTable($table) {
        $this->table = $table;

        return $this;
    }

    /**
     * Model constructor
     */
    public function __construct() {
        // Salt
        $this->salt = '144c5e9915b561abcbb6dabd3ac87635';

        $this->tempReturnType = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;

        if (is_null($validation)) {
            // $validation = \Config\Services::validation();
            $validation = new \stdClass();
        }
        $this->validation = $validation;
    }

    /**
     * Sets whether or not we should whitelist data set during
     * updates or inserts against $this->availableFields.
     *
     * @param bool $protect
     *
     * @return $this
     */
    public function protect($protect = true) {
        $this->protectFields = $protect;

        return $this;
    }

    /**
     * Provides a shared instance of the Query Builder.
     *
     * @param string $table
     *
     * @return BaseBuilder|Database\QueryBuilder
     */
    protected function builder($table = null) {
        if ($this->builder instanceof TblMapper) {
            return $this->builder;
        }

        $table = empty($table) ? $this->table : $table;

        $this->builder = new TblMapper($table);

        return $this->builder;
    }

    /**
     * Fetches the row of database from $this->table with a primary key
     * matching $id.
     *
     * @param mixed|array $id One primary key or an array of primary keys
     *
     * @return array|object|null    The resulting row of data, or null.
     */
    public function find($id) {
        $builder = $this->builder();

        if ($this->tempUseSoftDeletes === true) {
            $builder->where('deleted', 0);
        }

        if (is_array($id)) {
            $row = $builder->whereIn($this->primaryKey, $id)
                    ->setResultType($this->tempReturnType)
                    ->get();
        } else {
            $row = $builder->where($this->primaryKey, $id)
                    ->setResultType($this->tempReturnType)
                    ->getOne();
        }

        $this->tempReturnType = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;

        return $row;
    }

    /**
     * Extract a subset of data
     *
     * @param      $key
     * @param null $value
     *
     * @return array|null The rows of data.
     */
    public function findWhere($key, $value = null) {
        $builder = $this->builder();

        if ($this->tempUseSoftDeletes === true) {
            $builder->where('deleted', 0);
        }

        $rows = $builder->where($key, $value)
                ->setResultType($this->tempReturnType)
                ->get();

        $this->tempReturnType = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;

        return $rows;
    }

    /**
     * Finds a single record by a "hashed" primary key. Used in conjunction
     * with $this->getIDHash().
     *
     * THIS IS NOT VALID TO USE FOR SECURITY!
     *
     * @param string $hashedID
     *
     * @return array|null|object
     */
    public function findByHashedID($hashedID) {
        return $this->find($this->decodeID($hashedID));
    }

    /**
     * Works with the current Query Builder instance to return
     * all results, while optionally limiting them.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array|null
     */
    public function findAll($limit = 0, $offset = 0) {
        $builder = $this->builder();

        if ($this->tempUseSoftDeletes === true) {
            $builder->where('deleted', 0);
        }

        $row = $builder->limit($limit, $offset)
                ->setResultType($this->tempReturnType)
                ->get();

        $this->tempReturnType = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;

        return $row;
    }

    /**
     * Returns the first row of the result set. Will take any previous
     * Query Builder calls into account when determing the result set.
     *
     * @return array|object|null
     */
    public function first() {
        $builder = $this->builder();

        if ($this->tempUseSoftDeletes === true) {
            $builder->where('deleted', 0);
        }

        // Some databases, like PostgreSQL, need order
        // information to consistently return correct results.
        if (empty($builder->_orderBy)) {
            $builder->orderBy($this->primaryKey, 'asc');
        }

        $row = $builder->limit(1, 0)
                ->setResultType($this->tempReturnType)
                ->getOne();

        $this->tempReturnType = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;

        return $row;
    }

    /**
     * Sets $useSoftDeletes value so that we can temporarily override
     * the softdeletes settings. Can be used for all find* methods.
     *
     * @param bool $val
     *
     * @return $this
     */
    public function withDeleted($val = true) {
        $this->tempUseSoftDeletes = !$val;

        return $this;
    }

    /**
     * Works with the find* methods to return only the rows that
     * have been deleted.
     *
     * @return $this
     */
    public function onlyDeleted() {
        $this->tempUseSoftDeletes = false;

        $this->builder()
                ->where('deleted', 1);

        return $this;
    }

    /**
     * Inserts data into the current table. If an object is provided,
     * it will attempt to convert it to an array.
     *
     * @param $data
     *
     * @return bool
     */
    public function insert($data) {
        // If $data is using a custom class with public or protected
        // properties representing the table elements, we need to grab
        // them as an array.
        if (is_object($data) && !$data instanceof \stdClass) {
            $data = $this->classToArray($data);
        }

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false) {
            if ($this->validate($data) === false) {
                return false;
            }
        }

        // Must be called first so we don't
        // strip out created_at values.
        $data = $this->doProtectFields($data);

        if ($this->useTimestamps && !array_key_exists($this->createdField, $data)) {
            $data[$this->createdField] = $this->setDate();
        }

        if (empty($data)) {
            throw new \Exception('No data to insert.');
        }

        // Must use the set() method to ensure objects get converted to arrays
        return $this->builder()
                        ->set($data)
                        ->insert();
    }

    /**
     * Updates a single record in $this->table. If an object is provided,
     * it will attempt to convert it into an array.
     *
     * @param $id
     * @param $data
     *
     * @return bool
     */
    public function update($id, $data) {
        // If $data is using a custom class with public or protected
        // properties representing the table elements, we need to grab
        // them as an array.
        if (is_object($data) && !$data instanceof \stdClass) {
            $data = $this->classToArray($data);
        }

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false) {
            if ($this->validate($data) === false) {
                return false;
            }
        }

        // Must be called first so we don't
        // strip out updated_at values.
        $data = $this->doProtectFields($data);

        if ($this->useTimestamps && !array_key_exists($this->updatedField, $data)) {
            $data[$this->updatedField] = $this->setDate();
        }

        if (empty($data)) {
            throw new \Exception('No data to update.');
        }

        // Must use the set() method to ensure objects get converted to arrays
        return $this->builder()
                        ->where($this->primaryKey, $id)
                        ->set($data)
                        ->update();
    }

    /**
     * A convenience method that will attempt to determine whether the
     * data should be inserted or updated. Will work with either
     * an array or object. When using with custom class objects,
     * you must ensure that the class will provide access to the class
     * variables, even if through a magic method.
     *
     * @param $data
     *
     * @return bool
     */
    public function save($data) {
        $saveData = $data;

        // If $data is using a custom class with public or protected
        // properties representing the table elements, we need to grab
        // them as an array.
        if (is_object($data) && !$data instanceof \stdClass) {
            $data = $this->classToArray($data);
        }

        if (is_object($data) && isset($data->{$this->primaryKey})) {
            $response = $this->update($data->{$this->primaryKey}, $data);
        } elseif (is_array($data) && isset($data[$this->primaryKey])) {
            $response = $this->update($data[$this->primaryKey], $data);
        } else {
            $response = $this->insert($data);
        }

        // If it was an Entity class, check it for an onSave method.
        if (is_object($saveData) && !$saveData instanceof \stdClass) {
            if (method_exists($saveData, 'onSave')) {
                $saveData->onSave();
            }
        }

        return $response;
    }

    /**
     * Deletes a single record from $this->table where $id matches
     * the table's primaryKey
     *
     * @param mixed $id    The rows primary key
     * @param bool  $purge Allows overriding the soft deletes setting.
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function delete($id, $purge = false) {
        if ($this->useSoftDeletes && !$purge) {
            return $this->builder()
                            ->where($this->primaryKey, $id)
                            ->update(array('deleted' => 1));
        }

        return $this->builder()
                        ->where($this->primaryKey, $id)
                        ->delete();
    }

    /**
     * Deletes multiple records from $this->table where the specified
     * key/value matches.
     *
     * @param      $key
     * @param null $value
     * @param bool $purge Allows overriding the soft deletes setting.
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function deleteWhere($key, $value = null, $purge = false) {
        // Don't let them shoot themselves in the foot...
        if (empty($key)) {
            throw new \Exception('You must provided a valid key to deleteWhere.');
        }

        if ($this->useSoftDeletes && !$purge) {
            return $this->builder()
                            ->where($key, $value)
                            ->update(['deleted' => 1]);
        }

        return $this->builder()
                        ->where($key, $value)
                        ->delete();
    }

    /**
     * Permanently deletes all rows that have been marked as deleted
     * through soft deletes (deleted = 1)
     *
     * @return bool|mixed
     * @throws DatabaseException
     */
    public function purgeDeleted() {
        if (!$this->useSoftDeletes) {
            return true;
        }

        return $this->builder()
                        ->where('deleted', 1)
                        ->delete();
    }

    /**
     * Takes a class an returns an array of it's public and protected
     * properties as an array suitable for use in creates and updates.
     *
     * @param $data
     *
     * @return array
     */
    protected function classToArray($data) {
        $mirror = new \ReflectionClass($data);
        $props = $mirror->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

        $properties = array();

        // Loop over each property,
        // saving the name/value in a new array we can return.
        foreach ($props as $prop) {
            // Must make protected values accessible.
            $prop->setAccessible(true);
            $properties[$prop->getName()] = $prop->getValue($data);
        }

        return $properties;
    }

    /**
     * Set the value of the skipValidation flag.
     *
     * @param bool $skip
     *
     * @return $this
     */
    public function skipValidation($skip = true) {
        $this->skipValidation = $skip;

        return $this;
    }

    /**
     * Validate the data against the validation rules (or the validation group)
     * specified in the class property, $validationRules.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate($data) {
        if ($this->skipValidation === true || empty($this->validationRules)) {
            return true;
        }

        // Query Builder works with objects as well as arrays,
        // but validation requires array, so cast away.
        if (is_object($data)) {
            $data = (array) $data;
        }

        // ValidationRules can be either a string, which is the group name,
        // or an array of rules.
        if (is_string($this->validationRules)) {
            $valid = $this->validation->run($data, $this->validationRules);
        } else {
            $this->validation->setRules($this->validationRules, $this->validationMessages);
            $valid = $this->validation->run($data);
        }

        return (bool) $valid;
    }

    /**
     * Ensures that only the fields that are allowed to be updated
     * are in the data array.
     *
     * Used by insert() and update() to protect against mass assignment
     * vulnerabilities.
     *
     * @param $data
     *
     * @return mixed
     * @throws DatabaseException
     */
    protected function doProtectFields($data) {
        if ($this->protectFields === false)
            return $data;

        if (empty($this->allowedFields)) {
            throw new \Exception('No Allowed fields specified for model: ' . get_class($this));
        }

        foreach ($data as $key => $val) {
            if (!in_array($key, $this->allowedFields)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * A utility function to allow child models to use the type of
     * date/time format that they prefer. This is primarily used for
     * setting created_at and updated_at values, but can be used
     * by inheriting classes.
     *
     * The available time formats are:
     *  - 'int'      - Stores the date as an integer timestamp
     *  - 'datetime' - Stores the data in the SQL datetime format
     *  - 'date'     - Stores the date (only) in the SQL date format.
     *
     * @param int $userData An optional PHP timestamp to be converted.
     *
     * @return mixed
     */
    protected function setDate($userData = null) {
        $currentDate = is_numeric($userData) ? (int) $userData : time();

        switch ($this->dateFormat) {
            case 'int':
                return $currentDate;
                break;
            case 'datetime':
                return date('Y-m-d H:i:s', $currentDate);
                break;
            case 'date':
                return date('Y-m-d', $currentDate);
                break;
        }
    }

    /**
     * Sets the return type of the results to be as an associative array.
     */
    public function asArray() {
        $this->tempReturnType = 'array';

        return $this;
    }

    /**
     * Sets the return type to be of the specified type of object.
     * Defaults to a simple object, but can be any class that has
     * class vars with the same name as the table columns, or at least
     * allows them to be created.
     *
     * @param string $class
     *
     * @return $this
     */
    public function asObject($class = 'object') {
        $this->tempReturnType = $class;

        return $this;
    }

    /**
     * Loops over records in batches, allowing you to operate on them.
     * Works with $this->builder to get the Compiled select to
     * determine the rows to operate on.
     *
     * @param int      $size
     * @param \Closure $userFunc
     *
     * @throws DatabaseException
     */
    public function chunk($size, $userFunc) {
        $total = $this->builder()
                ->getCount();

        $offset = 0;

        while ($offset <= $total) {
            $builder = clone($this->builder());

            $rows = $builder->get($size, $offset);

            if ($rows === false) {
                throw new \Exception('Unable to get results from the query.');
            }

            $offset += $size;

            if (empty($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if ($userFunc($row) === false) {
                    return;
                }
            }
        }
    }

    /**
     * Returns a "hashed id", which isn't really hashed, but that's
     * become a fairly common term for this. Essentially creates
     * an obfuscated id, intended to be used to disguise the
     * ID from incrementing IDs to get access to things they shouldn't.
     *
     * THIS IS NOT VALID TO USE FOR SECURITY!
     *
     * Note, at some point we might want to move to something more
     * complex. The hashid library is good, but only works on integers.
     *
     * @see http://hashids.org/php/
     * @see http://raymorgan.net/web-development/how-to-obfuscate-integer-ids/
     *
     * @param $id
     *
     * @return mixed
     */
    public function encodeID($id) {
        // Strings don't currently have a secure
        // method, so simple base64 encoding will work for now.
        if (!is_numeric($id)) {
            return '=_' . base64_encode($id);
        }

        $id = (int) $id;
        if ($id < 1) {
            return false;
        }
        if ($id > pow(2, 31)) {
            return false;
        }

        $segment1 = $this->getHash($id, 16);
        $segment2 = $this->getHash($segment1, 8);
        $dec = (int) base_convert($segment2, 16, 10);
        $dec = ($dec > $id) ? $dec - $id : $dec + $id;
        $segment2 = base_convert($dec, 10, 16);
        $segment2 = str_pad($segment2, 8, '0', STR_PAD_LEFT);
        $segment3 = $this->getHash($segment1 . $segment2, 8);
        $hex = $segment1 . $segment2 . $segment3;
        $bin = pack('H*', $hex);
        $oid = base64_encode($bin);
        $oid = str_replace(array('+', '/', '='), array('$', ':', ''), $oid);

        return $oid;
    }

    /**
     * Decodes our hashed id.
     *
     * @see http://raymorgan.net/web-development/how-to-obfuscate-integer-ids/
     *
     * @param $hash
     *
     * @return mixed
     */
    public function decodeID($hash) {
        // Was it a simple string we encoded?
        if (substr($hash, 0, 2) == '=_') {
            $hash = substr($hash, 2);
            return base64_decode($hash);
        }

        if (!preg_match('/^[A-Z0-9\:\$]{21,23}$/i', $hash)) {
            return 0;
        }
        $hash = str_replace(array('$', ':'), array('+', '/'), $hash);
        $bin = base64_decode($hash);
        $hex = unpack('H*', $bin);
        $hex = $hex[1];
        if (!preg_match('/^[0-9a-f]{32}$/', $hex)) {
            return 0;
        }
        $segment1 = substr($hex, 0, 16);
        $segment2 = substr($hex, 16, 8);
        $segment3 = substr($hex, 24, 8);
        $exp2 = $this->getHash($segment1, 8);
        $exp3 = $this->getHash($segment1 . $segment2, 8);
        if ($segment3 != $exp3) {
            return 0;
        }
        $v1 = (int) base_convert($segment2, 16, 10);
        $v2 = (int) base_convert($exp2, 16, 10);
        $id = abs($v1 - $v2);

        return $id;
    }

    /**
     * Used for our hashed IDs. Requires $salt to be defined
     * within the Config\App file.
     *
     * @param $str
     * @param $len
     *
     * @return string
     */
    protected function getHash($str, $len) {
        return substr(sha1($str . $this->salt), 0, $len);
    }

    /**
     * Provides direct access to method in the builder (if available)
     * and the database connection.
     *
     * @param string $name
     * @param array  $params
     *
     * @return $this|null
     */
    public function __call($name, array $params) {
        $result = null;

        if (method_exists($this->builder(), $name)) {
            $result = call_user_func_array(array($this->builder(), $name), $params);
        }

        // Don't return the builder object, since
        // that will interrupt the usability flow
        // and break intermingling of model and builder methods.
        if (empty($result)) {
            return $result;
        }
        if (!$result instanceof TblMapper) {
            return $result;
        }

        return $this;
    }

    /**
     * Provides/instantiates the builder/db connection.
     *
     * @param string $name
     *
     * @return null
     */
    public function __get($name) {
        if (isset($this->builder()->$name)) {
            return $this->builder()->$name;
        }

        return null;
    }

}
