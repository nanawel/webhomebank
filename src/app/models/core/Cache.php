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

    protected $_configCacheKey;

    protected $_configCacheKeySections = [
        'HTML_LANG',
        'ENCODING',
        'LANGUAGE',
        'FALLBACK',
        'CACHE',
        'app'
    ];

    public function __construct(protected $params = []) {
        $this->_softBackend = \Cache::instance();
    }

    protected function _prepareKey(string $key) {
        return \Base::instance()->hash($key . $this->_getConfigCacheKey());
    }

    public function exists($key, &$val = null): mixed
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array([$this->_softBackend, __FUNCTION__], [$key, &$val]);
    }

    public function set($key, $val, $ttl = 0): mixed
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array([$this->_softBackend, __FUNCTION__], [$key, $val, $ttl]);
    }

    public function get($key): mixed
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array([$this->_softBackend, __FUNCTION__], [$key]);
    }

    public function clear($key): mixed
    {
        $key = $this->_prepareKey($key);
        return call_user_func_array([$this->_softBackend, __FUNCTION__], [$key]);
    }

    public function reset($suffix = null, $lifetime = 0): mixed
    {
        return call_user_func_array([$this->_softBackend, __FUNCTION__], func_get_args());
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
                $key = [];
                foreach ($this->_configCacheKeySections as $section) {
                    $key[] = $fw->get($section);
                }

                $this->_configCacheKey = $fw->hash($fw->serialize($key));
            }
        }

        return $this->_configCacheKey;
    }
}
