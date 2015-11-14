<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 13/07/15
 * Time: 11:28
 */

namespace app\models\core;

use Registry;
use Reflectionclass;
use Redis;
use Base;


/**
 * Default Cache class
 *
 * Simple wrapper for \Cache class provided by F3 but designed to be used by core classes and be derived to
 * add specific features (e.g. on-the-fly encryption or compression).
 *
 * Example of configuration:
 *     [app]
 *     CACHE_CLASS=\app\models\core\Cache
 *     CACHE_CLASS_PARAMS=param1=value1|param2=value2
 *
 * @see App
 *
 * @package app\models\core
 */
class Cache
{
    protected $_softBackend;
    protected $_params;

    protected $_configCacheKey;
    protected $_configCacheKeySections = array(
        'HTML_LANG',
        'ENCODING',
        'LANGUAGE',
        'FALLBACK',
        'CACHE',
        'app'
    );

    public function __construct($params = array()) {
        $this->_softBackend = \Cache::instance();
        $this->_params = $params;
    }

    protected function _prepareKey($key) {
        return \Base::instance()->hash($key . $this->_getConfigCacheKey());
    }

    function exists($key, &$val = null)
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array(array($this->_softBackend, __FUNCTION__), array($key, &$val));
    }

    function set($key, $val, $ttl = 0)
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array(array($this->_softBackend, __FUNCTION__), array($key, $val, $ttl));
    }

    function get($key)
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array(array($this->_softBackend, __FUNCTION__), array($key));
    }

    function clear($key)
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array(array($this->_softBackend, __FUNCTION__), array($key));
    }

    function reset($suffix = null, $lifetime = 0)
    {
        return call_user_func_array(array($this->_softBackend, __FUNCTION__), func_get_args());
    }

    /**
     * Generate a unique key based on the current configuration files.
     * Avoid using deprecated cache data when config is updated.
     *
     * This behavior can be turned off by setting a fixed value for app.CONFIG_CACHE_KEY inside configuration.
     *
     * @see $this->_configCacheKeySections for configuration sections used to calculate the key.
     *
     * @return string
     */
    protected function _getConfigCacheKey() {
        $fw = \Base::instance();
        if (!$this->_configCacheKey) {
            if ($key = Main::app()->getConfig('CONFIG_CACHE_KEY')) {
                $this->_configCacheKey = $key;
            }
            else {
                $key = array();
                foreach ($this->_configCacheKeySections as $section) {
                    $key[] = $fw->get($section);
                }
                $this->_configCacheKey = $fw->hash($fw->serialize($key));
            }
        }
        return $this->_configCacheKey;
    }
}