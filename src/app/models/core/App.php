<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 07/07/15
 * Time: 13:33
 */

namespace app\models\core;

use app\controllers\Core\AbstractController;

abstract class App
{
    const SESSION_DEFAULT = 'core';
    const CONFIG_KEY_PREFIX = 'app.';

    const DEFAULT_TMP_DIR = 'var/tmp/';

    protected $_sessions = array();
    protected $_customSessionsConfig;

    protected $_useCache = false;

    protected $_tmpDir;

    /**
     * @var AbstractController
     */
    protected $_currentController;

    /**
     * @var Cache
     */
    protected $_cache;


    /**
     * @return void
     */
    public final function setup() {
        $this->_useCache = \Base::instance()->get('CACHE') ? true : false;
        $this->_setup();
        $this->_initTmpDir();
    }

    /**
     * @return void
     */
    protected function _setup() {
        // to be overridden
    }

    public function setConfig($key, $value) {
        return \Base::instance()->set(self::CONFIG_KEY_PREFIX . $key, $value);
    }

    public function getConfig($key) {
        return \Base::instance()->get(self::CONFIG_KEY_PREFIX . $key);
    }

    /**
     * @param string $name
     * @return Session
     */
    public function getSession($name = self::SESSION_DEFAULT) {
        if ($name === null) {
            $name = self::SESSION_DEFAULT;
        }
        if (!isset($this->_sessions[$name])) {
            $customSessions = $this->_getCustomSessionsConfig();
            if (isset($customSessions[$name])) {
                $this->_sessions[$name] = new $customSessions[$name]($name);
            }
            else {
                $this->_sessions[$name] = new Session($name);
            }
        }
        return $this->_sessions[$name];
    }

    protected function _getCustomSessionsConfig() {
        if (!$this->_customSessionsConfig) {
            $config = explode(';', $this->getConfig('CUSTOM_SESSIONS'));
            foreach($config as $session) {
                list($key, $class) = explode(':', $session);
                $this->_customSessionsConfig[$key] = $class;
            }
        }
        return $this->_customSessionsConfig;
    }

    /**
     * @return Session[]
     */
    public function getSessions() {
        return $this->_sessions;
    }

    /**
     * @param AbstractController $currentController
     */
    public function setCurrentController(AbstractController $currentController) {
        $this->_currentController = $currentController;
    }

    /**
     * @return AbstractController
     */
    public function getCurrentController() {
        return $this->_currentController;
    }

    public function useCache() {
        return $this->_useCache;
    }

    public function getCacheInstance() {
        if ($this->_cache === null) {
            if ($cacheClass = $this->getConfig('CACHE_CLASS')) {
                $cacheParams = self::configToHashmap($this->getConfig('CACHE_CLASS_PARAMS'), '|', '=');
                $this->_cache = new $cacheClass($cacheParams);
            }
            else {
                $this->_cache = false;
            }
        }
        return $this->_cache;
    }

    /**
     * @param $key
     * @param $data
     * @param int $ttl
     * @return bool
     */
    public function saveCache($key, $data, $ttl = 0) {
        return $this->useCache() ? $this->getCacheInstance()->set($key, $data, $ttl) : false;
    }

    public function loadCache($key) {
        return $this->useCache() ? $this->getCacheInstance()->get($key) : false;
    }

    protected function _initTmpDir() {
        if ($tmpDir = \Base::instance()->get('TEMP')) {
            $this->_tmpDir = $tmpDir;
        }
        else {
            $this->_tmpDir = self::DEFAULT_TMP_DIR;
        }
        if (!is_dir($this->_tmpDir)) {
            if (!@mkdir($this->_tmpDir, 0770, true)) {
                throw new \Exception('Cannot create temporary directory in ' . realpath(dirname($this->_tmpDir)) . ', please adjust permissions first.');
            }
        }
        else {
            if (!is_writable($this->_tmpDir)) {
                throw new \Exception($this->_tmpDir. ' must be writable');
            }
        }
    }

    public function getTmpDir() {
        return $this->_tmpDir;
    }

    /**
     * @param $config
     * @return array
     */
    public static function configToHashmap($config, $valueSeparator = ';', $keyValueSeparator = ':') {
        $return = array();
        foreach(explode($valueSeparator, $config) as $c) {
            list($key, $value) = explode($keyValueSeparator, $c);
            $return[$key] = $value;
        }
        return $return;
    }
}