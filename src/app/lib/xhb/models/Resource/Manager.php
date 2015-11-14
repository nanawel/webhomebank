<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 16/09/15
 * Time: 08:30
 */

namespace xhb\models\Resource;

/**
 * Class Manager
 *
 * Holds objects that cannot be stored directly in models or resources (e.g. PDO)
 *
 * @package xhb\models\Resource
 */
class Manager
{
    protected static $_instance;

    protected $_data = array();

    /**
     * @return Manager
     */
    public static function instance() {
        if (self::$_instance === null) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }
        return self::$_instance;
    }

    public function setData($key, $value, $xhbId = null) {
        $data =& $this->_data;
        if ($xhbId !== null) {
            if (!isset($data[$xhbId])) {
                $data[$xhbId] = array();
            }
            $data =& $data[$xhbId];
        }
        if (is_array($key)) {
            $data = $key;
        }
        else {
            $data[$key] = $value;
        }
        return $this;
    }

    public function getData($key = '', $xhbId = null) {
        $data =& $this->_data;
        if ($xhbId !== null) {
            if (!isset($data[$xhbId])) {
                return null;
            }
            $data =& $data[$xhbId];
        }
        if (isset($data[$key])) {
            return $data[$key];
        }
        return null;
    }
} 