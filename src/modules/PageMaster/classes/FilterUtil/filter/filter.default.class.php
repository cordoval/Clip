<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: filter.default.class.php 25078 2008-12-17 08:39:04Z Guite $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Axel Guckelsberger <axel@zikula.org>
 * @category   Zikula_Core
 * @package    Object_Library
 * @subpackage FilterUtil
 */

Loader::loadClass('FilterUtil_Build', FILTERUTIL_CLASS_PATH);

/**
 * Default plugin main class
 *
 * @category   Zikula_Core
 * @package    Object_Library
 * @subpackage FilterUtil
 * @author     Philipp Niethammer <philipp@zikula.org>
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @link       http://www.zikula.org 
 */
class FilterUtil_Filter_default extends FilterUtil_PluginCommon implements FilterUtil_Build
{
    private $ops = array();
    private $fields = array();

    /**
     * Constructor
     *
     * @access public
     * @param array $config Configuration
     * @return object FilterUtil_Plugin_Default
     */
    public function __construct($config)
    {
        parent::__construct($config);

        if (isset($config['fields']) && (!isset($this->fields) || !is_array($this->fields))) {
            $this->addFields($config['fields']);
        }

        if (isset($config['ops']) && (!isset($this->ops) || !is_array($this->ops))) {
            $this->activateOperators($config['ops']);
        } else {
            $this->activateOperators(array('eq', 'ne', 'lt', 'le', 'gt', 'ge', 'like', 'likefirst' , 'null', 'notnull'));
        }

        if (isset($config['default']) && $config['default'] == true || count($this->fields) <= 0) {
            $this->default = true;
        }
    }
    
    /**
     * Adds fields to list in common way
     *
     * @access public
     * @param mixed $op Operators to activate
     */
    public function activateOperators($op)
    {
        static $ops = array('eq', 'ne', 'lt', 'le', 'gt', 'ge', 'like', 'likefirst' , 'null', 'notnull');

        if (is_array($op)) {
            foreach($op as $v) {
                $this->activateOperators($v);
            }
        } elseif (!empty($op) && array_search($op, $this->ops) === false && array_search($op, $ops) !== false) {
            $this->ops[] = $op;
        }
    }

    public function addFields($fields)
    {
        if (is_array($fields)) {
            foreach($fields as $fld) {
                $this->addFields($fld);
            }
        } elseif (!empty($fields) && $this->fieldExists($fields) 
                && array_search($fields, $this->fields) === false) {
            $this->fields[] = $fields;
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get operators
     *
     * @access public
     * @return array Set of Operators and Arrays
     */
    public function getOperators()
    {
        $fields = $this->getFields();
        if ($this->default == true) {
            $fields[] = '-';
        }

        $ops = array();
        foreach ($this->ops as $op) {
            $ops[$op] = $fields;
        }

        return $ops;
    }

    /**
     * return SQL code
     *
     * @access public
     * @param string $field Field name
     * @param string $op Operator
     * @param string $value Test value
     * @return string SQL code
     */
    public function getSQL($field, $op, $value)
    {
        if (!$this->fieldExists($field)) {
            return '';
        }

        $where = '';
        $column = $this->column[$field];

        switch ($op) {
            case 'ne':
                $where = "$column <> '$value'";
                break;

            case 'lt':
                $where = "$column < '$value'";
                break;

            case 'le':
                $where = "$column <= '$value'";
                break;

            case 'gt':
                $where = "$column > '$value'";
                break;

            case 'ge':
                $where = "$column >= '$value'";
                break;

            case 'like':
                $where = "$column LIKE '$value'";
                break;

            case 'likefirst':
                $where = "$column LIKE '$value%'";
                break;

            case 'null':
                $where = "$column = '' OR $column IS NULL";
                break;

            case 'notnull':
                $where = "$column <> '' OR $column IS NOT NULL";
                break;

            case 'eq':
                $where = "$column = '$value'";
                break;
        }

        return array('where' => $where); 
    }
}
