<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Validator\Db;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql\Select as DbSelect;
use Zend\Validator\AbstractValidator;
use Zend\Validator\Exception;

/**
 * Class for Database record validation
 *
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AbstractDb extends AbstractValidator
{
    /**
     * Error constants
     */
    const ERROR_NO_RECORD_FOUND = 'noRecordFound';
    const ERROR_RECORD_FOUND    = 'recordFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_NO_RECORD_FOUND => "No record matching '%value%' was found",
        self::ERROR_RECORD_FOUND    => "A record matching '%value%' was found",
    );

    /**
     * Select object to use. can be set, or will be auto-generated
     *
     * @var DbSelect
     */
    protected $_select;

    /**
     * @var string
     */
    protected $_schema = null;

    /**
     * @var string
     */
    protected $_table = '';

    /**
     * @var string
     */
    protected $_field = '';

    /**
     * @var mixed
     */
    protected $_exclude = null;

    /**
     * Database adapter to use. If null isValid() will throw an exception
     *
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $_adapter = null;

    /**
     * Provides basic configuration for use with Zend\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * Exclude can either be a String containing a where clause, or an array with `field` and `value` keys
     * to define the where clause added to the sql.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
     * 'field'   => The field to check for a match
     * 'adapter' => The database adapter to use
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     *
     * @param array|Traversable|DbSelect $options Options to use for this validator
     * @throws \Zend\Validator\Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct();

        if ($options instanceof DbSelect) {
            $this->setSelect($options);
            return;
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } else if (func_num_args() > 1) {
            $options       = func_get_args();
            $temp['table'] = array_shift($options);
            $temp['field'] = array_shift($options);

            if (!empty($options)) {
                $temp['adapter'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['exclude'] = array_shift($options);
            }

            $options = $temp;
        }

        if (!array_key_exists('table', $options) && !array_key_exists('schema', $options)) {
            throw new Exception\InvalidArgumentException('Table or Schema option missing!');
        }

        if (!array_key_exists('field', $options)) {
            throw new Exception\InvalidArgumentException('Field option missing!');
        }

        if ( !array_key_exists('adapter', $options) || !$options['adapter'] instanceof DbAdapter ) {
            throw new Exception\InvalidArgumentException('No database adapter present!');
        }

        if (array_key_exists('exclude', $options)) {
            $this->setExclude($options['exclude']);
        }

        $this->setField($options['field']);
        $this->setAdapter($options['adapter']);
        if (array_key_exists('table', $options)) {
            $this->setTable($options['table']);
        }

        if (array_key_exists('schema', $options)) {
            $this->setSchema($options['schema']);
        }
    }

    /**
     * Returns the set adapter
     *
     * @return DbAdapter
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Sets a new database adapter
     *
     * @param  DbAdapter $adapter
     * @return self Provides a fluent interface
     */
    public function setAdapter(DbAdapter $adapter)
    {
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * Returns the set exclude clause
     *
     * @return string|array
     */
    public function getExclude()
    {
        return $this->_exclude;
    }

    /**
     * Sets a new exclude clause
     *
     * @param string|array $exclude
     * @return self Provides a fluent interface
     */
    public function setExclude($exclude)
    {
        $this->_exclude = $exclude;
        return $this;
    }

    /**
     * Returns the set field
     *
     * @return string|array
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Sets a new field
     *
     * @param string $field
     * @return AbstractDb
     */
    public function setField($field)
    {
        $this->_field = (string)$field;
        return $this;
    }

    /**
     * Returns the set table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Sets a new table
     *
     * @param string $table
     * @return self Provides a fluent interface
     */
    public function setTable($table)
    {
        $this->_table = (string)$table;
        return $this;
    }

    /**
     * Returns the set schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->_schema;
    }

    /**
     * Sets a new schema
     *
     * @param string $schema
     * @return self Provides a fluent interface
     */
    public function setSchema($schema)
    {
        $this->_schema = $schema;
        return $this;
    }

    /**
     * Sets the select object to be used by the validator
     *
     * @param  DbSelect $select
     * @return self Provides a fluent interface
     */
    public function setSelect(DbSelect $select)
    {
        $this->_select = $select;
        return $this;
    }

    /**
     * Gets the select object to be used by the validator.
     * If no select object was supplied to the constructor,
     * then it will auto-generate one from the given table,
     * schema, field, and adapter options.
     *
     * @return DbSelect The Select object which will be used
     */
    public function getSelect()
    {
        if (null === $this->_select) {
            $adapter  = $this->getAdapter();
            $driver   = $adapter->getDriver();
            $platform = $adapter->getPlatform();

            /**
             * Build select object
             */
            $select = new DbSelect();
            $select->from($this->_table, $this->_schema)->columns(
                array($this->_field)
            );

            // Support both named and positional parameters
            if ('named' == $driver->getPrepareType()) {
                $select->where(
                    $platform->quoteIdentifier($this->_field, true) . ' = :value'
                );
            } else {
                $select->where(
                    $platform->quoteIdentifier($this->_field, true) . ' = ?'
                );
            }

            if ($this->_exclude !== null) {
                if (is_array($this->_exclude)) {
                    $select->where(
                        $platform->quoteIdentifier($this->_exclude['field'], true) .
                        ' != ?', $this->_exclude['value']
                    );
                } else {
                    $select->where($this->_exclude);
                }
            }

            $this->_select = $select;
        }

        return $this->_select;
    }

    /**
     * Run query and returns matches, or null if no matches are found.
     *
     * @param  string $value
     * @return array when matches are found.
     */
    protected function _query($value)
    {
        $adapter  = $this->getAdapter();
        $statement = $adapter->createStatement();
        $this->getSelect()->prepareStatement($adapter, $statement);

        return $statement->execute(array('value' => $value))->current();
    }
}
