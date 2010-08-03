<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: filter.date.class.php 25078 2008-12-17 08:39:04Z Guite $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Axel Guckelsberger <axel@zikula.org>
 * @category   Zikula_Core
 * @package    Object_Library
 * @subpackage FilterUtil
 */

Loader::loadClass('FilterUtil_Build', FILTERUTIL_CLASS_PATH);
Loader::loadClass('FilterUtil_Replace', FILTERUTIL_CLASS_PATH);

/**
 * Date plugin main class
 *
 * @category   Zikula_Core
 * @package    Object_Library
 * @subpackage FilterUtil
 * @author     Philipp Niethammer <philipp@zikula.org>
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @link       http://www.zikula.org 
 */
class FilterUtil_Filter_date extends FilterUtil_PluginCommon implements FilterUtil_Build, FilterUtil_Replace
{
    private $ops = array();
    private $fields = array();

    public function __construct($config)
    {
        parent::__construct($config);

        if (isset($config['fields']) && (!isset($this->fields) || !is_array($this->fields))) {
            $this->addFields($config['fields']);
        }

        if (isset($config['ops']) && (!isset($this->ops) || !is_array($this->ops))) {
            $this->activateOperators($config['ops']);
        } else {
            $this->activateOperators($this->availableOperators());
        }
    }
    
    public function availableOperators()
    {
        return array('eq', 'ne', 'gt', 'ge', 'lt', 'le');
    }
    
    /**
     * Adds fields to list in common way
     *
     * @access public
     * @param mixed $op Operators to activate
     */
    public function activateOperators($op)
    {
        if (is_array($op)) {
            foreach($op as $v) {
                $this->activateOperators($v);
            }
        } elseif (!empty($op) && array_search($op, $this->ops) === false && array_search($op, $this->availableOperators()) !== false) {
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
     * Replace field's value
     *
     * @param string $field Field name
     * @param string $op Filter operator
     * @param string $value Filter value
     * @return string New filter value
     */
    public function replace($field, $op, $value)
    {
        // First check if this plugin have to work with this field
        if (array_search($field, $this->fields) === false) {
            return array($field, $op, $value); // If not, return given set
        }

        // Now, work!
        // convert to unix timestamp
        if (($date = $this->DateConvert($value)) === false) {
            return false;
        }

        return array($field, $op, $date);
    }

    protected function DateConvert($date)
    {
        if (strptime($date, "%d.%m.%Y %H:%M:%S") !== false) {
            $arr = strptime($date, "%d.%m.%Y %H:%M:%S");
            $time = DateUtil::buildDatetime($arr['tm_year'], $arr['tm_mon'], $arr['tm_monday'], $arr['tm_hour'], $arr['tm_min'], $arr['tm_sec']);
        } elseif (is_numeric($date)) {
            $time = DateUtil::getDatetime($date);
        } else {
            $time = str_replace('_', ' ', $date);
        }

        return $time;
    }
    
    private function makePeriod($date, $type)
    {
        $datearray = getdate($date);

        switch ($type) {
            case 'year':
                $from = mktime(0, 0, 0, 1, 1, $datearray['year']);
                $to = strtotime('+1 year', $from);
                break;

            case 'month':
                $from = mktime(0, 0, 0, $datearray['mon'], 1, $datearray['year']);
                $to = strtotime('+1 month', $from);
                break;
            /*
            case 'week':
                $from = DateUtil::getDatetime("substr ($date, 0, strpos ($date, ' '));
                $to = DateUtil::getDatetime("$from +1 year");
            */
            case 'day':
            case 'tomorrow':
                $from = mktime(0, 0, 0, $datearray['mon'], $datearray['mday'], $datearray['year']);
                $to = strtotime('+1 day', $from);
                break;

            case 'hour':
                $from = mktime($datearray['hours'], 0, 0, $datearray['mon'], $datearray['mday'], $datearray['year']);
                $to = $from + 3600;
                break;

            case 'min':
            case 'minute':
                $from = mktime($datearray['hours'], $datearray['minutes'], 0, $datearray['mon'], $datearray['mday'], $datearray['year']);
                $to = $from + 60;
                break;
        }

        return array($from, $to);
    }
    
    public function GetSQL($field, $op, $value)
    {
        if (array_search($op, $this->ops) === false || array_search($field, $this->fields) === false) {
            return '';
        }

        $type = 'point';
        if (preg_match('~^(year|month|week|day|hour|min):\s*(.*)$~i', $value, $res)) {
            $type = strtolower($res[1]);
            $time = strtotime($res[2]);
        } elseif(preg_match('~(year|month|week|day|hour|min|tomorrow)~', $value, $res)) {
            $type = strtolower($res[1]);
            $time = strtotime($value);
        } else {
            $time = strtotime($value);
        }
        
        $column = $this->column[$field];

        switch ($op) {
            case 'eq':
                if ($type != 'point') {
                    list($from, $to) = $this->makePeriod($time, $type);
                    $where =  "$column >= '".DateUtil::getDatetime($from)."' AND ".
                              "$column < '".DateUtil::getDatetime($to)."'";
                } else {
                    $where = "$column = '".DateUtil::getDatetime($time)."'";
                }
                break;

            case 'ne':
                if ($type != 'point') {
                    list($from, $to) = $this->makePeriod($time, $type);
                    $where =  "$column < '".DateUtil::getDatetime($from)."' AND ".
                              "$column >= '".DateUtil::getDatetime($to)."'";
                } else {
                    $where = "$column <> '".DateUtil::getDatetime($time)."'";
                }
                break;

            case 'gt':
                if ($type != 'point') {
                    list($from, $time) = $this->makePeriod($time, $type);
                }
                $where = "$column > '".DateUtil::getDatetime($time)."'";
                break;

            case 'ge':
                $where = "$column >= '".DateUtil::getDatetime($time)."'";
                break;

            case 'lt':
                $where = "$column < '".DateUtil::getDatetime($time)."'";
                break;

            case 'le':
                if ($type != 'point') {
                    list($from, $time) = $this->makePeriod($time, $type);
                }
                $where = "$column <= '".DateUtil::getDatetime($time)."'";
                break;
        }

        return array('where' => $where);
    }
}
