<?php
namespace Xhb\Util;

class MagicObject
{
    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
    */
    protected static $_underscoreCache = array();

    public function __construct() {
        $args = func_get_args();
        if (empty($args[0])) {
            $args[0] = array();
        }
        $this->_data = $args[0];
    }

    /**
     * Get value from _data array without parse key
     *
     * @param   string $key
     * @return  mixed
     */
    protected function _getData($key) {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     * @return \Xhb\Util\MagicObject
     */
    public function addData(array $arr) {
        foreach($arr as $index=>$value) {
            $this->setData($index, $value);
        }
        return $this;
    }

    /**
     * Overwrite data in the object.
     *
     * $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array $key
     * @param mixed $value
     * @return \Xhb\Util\MagicObject
     */
    public function setData($key, $value=null) {
        if(is_array($key)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }
        return $this;
    }

    /**
     * Unset data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * @param string $key
     * @return \Xhb\Util\MagicObject
     */
    public function unsetData($key=null) {
        if (is_null($key)) {
            $this->_data = array();
        } else {
            unset($this->_data[$key]);
        }
        return $this;
    }

    /**
     * If $key is empty, checks whether there's any data in the object
     * Otherwise checks if the specified attribute is set.
     *
     * @param string $key
     * @return boolean
     */
    public function hasData($key = '') {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }
        return array_key_exists($key, $this->_data);
    }

    /**
     * Retrieves data from the object
     *
     * If $key is empty will return all the data as an array
     * Otherwise it will return value of the attribute specified by $key
     *
     * If $index is specified it will assume that attribute data is an array
     * and retrieve corresponding member.
     *
     * @param string $key
     * @param string|int $index
     * @return mixed
     */
    public function getData($key = '') {
        if ('' === $key) {
            return $this->_data;
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return null;
    }

    public function getDataUsingMethod($key) {
        return call_user_func(array($this, 'get' . self::_camelize($key)));
    }

    /**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args) {
        switch (substr($method, 0, 3)) {
            case 'get' :
                $key = self::_underscore(substr($method,3));
                $data = $this->getData($key, isset($args[0]) ? $args[0] : null);
                return $data;

            case 'set' :
                $key = self::_underscore(substr($method,3));
                $result = $this->setData($key, isset($args[0]) ? $args[0] : null);
                return $result;

            case 'uns' :
                $key = self::_underscore(substr($method,3));
                $result = $this->unsetData($key);
                return $result;

            case 'has' :
                $key = self::_underscore(substr($method,3));
                return isset($this->_data[$key]);
        }
        throw new \Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
    }

    /**
     * Converts field names for setters and geters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unneccessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected static function _underscore($name) {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        self::$_underscoreCache[$name] = $result;
        return $result;
    }

    protected static function _camelize($name) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
}