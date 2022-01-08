<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers\Core;


use app\models\core\Log;
use app\models\core\Main;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\core\Session;
use app\models\core\Url;
use app\models\core\View;

/**
 * Class AbstractController
 *
 * @package app\controllers\Core
 */
abstract class AbstractController
{
    const CACHE_REQUEST_OUTPUT_PREFIX     = 'REQUEST_';
    const CACHE_REQUEST_OUTPUT_TTL        = 120;
    const CACHE_DEFAULT_TTL               = 600;
    const PAGE_BLOCK_NAME                 = 'page';
    const PAGE_TEMPLATE_DEFAULT           = 'layout.phtml';

    /* @var $_fw \Base */
    protected $_fw = null;

    protected $_controller = null;
    protected $_action = null;
    protected $_rawRequestParams;
    protected $_requestParams = array();

    protected $_viewInstance = null;

    protected $_shouldRender = true;
    protected $_renderedFromCache = true;
    protected $_canCacheOutput = true;

    public final function __construct($fw) {
        $this->_fw = $fw;
        Main::instance()->setup();
        Main::app()->setCurrentController($this);
        $this->setPageTemplate(self::PAGE_TEMPLATE_DEFAULT);
        $this->_init();
    }

    protected function _init() {
        // to be overridden
    }

    public function getSession($name = null) {
        return Main::app()->getSession($name);
    }

    protected function _initRequestData($args) {
        $parts = explode('/', trim(preg_replace('#(.*?)\?(.*)#', '$1', $args[0]), '/'));
        $controller = array_shift($parts);
        if ($controller === null) {
            $controller = 'index';
        }
        $action = array_shift($parts);
        if ($action === null) {
            $action = 'index';
        }
        $this->_controller = $controller;
        $this->_action = $action;
        $this->_setRequestParams($parts);
    }

    public function getControllerName() {
        return $this->_controller;
    }

    public function getActionName() {
        return $this->_action;
    }

    protected function _setRequestParams($params) {
        $parts = array_chunk($params, 2);
        $keys = array_column($parts, 0);
        $values = array_column($parts, 1);

        if (count($values) < count($keys)) {
            $values[] = null;
        }
        $this->_requestParams = array_combine($keys, $values);
        return $this;
    }

    protected function _getRequestParams() {
        return $this->_requestParams;
    }

    protected function _getRequestParam($param) {
        return isset($this->_requestParams[$param]) ? $this->_requestParams[$param] : null;
    }

    public function getRequestQuery($param = null) {
        $query = $this->_fw->get('REQUEST');
        if ($param === null) {
            return $query;
        }
        return isset($query[$param]) ? $query[$param] : null;
    }

    public function getFullActionName() {
        return $this->_controller . '/' . $this->_action;
    }

    protected function _setContentTemplate() {
        $fullAction = $this->getFullActionName();
        $defaultTemplate = str_replace(array('_', '/'), DIRECTORY_SEPARATOR, $fullAction) . View::TEMPLATE_DEFAULT_EXTENSION;
        $this->getView()->setBlockTemplate(View::TEMPLATE_CONTENT_KEY, $defaultTemplate);
        return $this;
    }

    protected function _addBodyClass() {
        $class = preg_replace('/[^a-z-]/i', '-', $this->_controller);
        Design::instance()->addBodyClass($class);
        $class = preg_replace('/[^a-z-]/i', '-', $this->getFullActionName());
        Design::instance()->addBodyClass($class);
    }

    /**
     * @return string
     */
    public function getPageTitle() {
        return $this->_fw->get('PAGE_TITLE');
    }

    /**
     * @param string $title
     */
    public function setPageTitle($title) {
        $this->_fw->set('PAGE_TITLE', $title);
        return $this;
    }

    /**
     * @param bool $shouldRender
     */
    public function setShouldRender($shouldRender) {
        $this->_shouldRender = $shouldRender ? true : false;
        return $this;
    }

    /**
     * @return bool
     */
    public function getShouldRender() {
        return $this->_shouldRender;
    }

    /**
     * @param string $template
     */
    public function setPageTemplate($template) {
        $this->getView()->setBlockTemplate(self::PAGE_BLOCK_NAME, $template);
        return $this;
    }

    /**
     * @param string $template
     */
    public function setPageConfig($config) {
        $this->getView()->setBlockConfig(self::PAGE_BLOCK_NAME, $config);
        return $this;
    }

    /**
     * @return string
     */
    public function getPageTemplate() {
        return $this->getView()->getBlockTemplate(self::PAGE_BLOCK_NAME);
    }

    /**
     * @param \Base $fw
     * @param string $args
     */
    public final function beforeRoute($fw, $args = null) {
        $this->_rawRequestParams = $args;
        $this->_initRequestData($args);

        $this->_setContentTemplate();
        $this->_addBodyClass();
        $this->setPageTitle($fw->get('app.TITLE') . '&nbsp;' . $fw->get('app.VERSION'));

        if ($this->_beforeRoute($fw, $args) === false) {
            return false;
        }

        $beforeMethod = '_' . $this->getActionName() . 'ActionBefore';
        if (method_exists($this, $beforeMethod)) {
            if (!$this->$beforeMethod($fw, $args) === false) {
                return false;
            }
        }

        if ($this->_renderedFromCache = $this->_renderFromCache()) {
            $this->__afterRender($fw, $args);
            // Rendered from cache successfully, so skip @action() method and afterRoute()
            return false;
        }
        return true;
    }

    /**
     * @param \Base $fw
     * @param string $args
     */
    public final function afterRoute($fw, $args = null) {
        if ($this->_afterRoute($fw, $args) === false) {
            return false;
        }
        $this->_render();
        $this->__afterRender($fw, $args);
    }

    private function __afterRender($fw, $args = null) {
        $this->getSession()->set('last_url', $fw->get('REALM'));
        $this->_afterRender($fw, $args);
    }

    /**
     * @param $fw
     * @param null $args
     * @return bool False to stop request processing.
     */
    protected function _beforeRoute($fw, $args = null) {
        // to be overridden
    }

    /**
     * @param $fw
     * @param null $args
     * @return bool False to stop request processing.
     */
    protected function _afterRoute($fw, $args = null) {
        // to be overridden
    }

    protected function _afterRender($fw, $args = null) {
        // to be overridden
    }

    /**
     * @return View
     */
    public function getView() {
        return $this->_viewInstance ? $this->_viewInstance : View::instance();
    }

    protected function _render() {
        if ($this->_shouldRender) {
            ob_start();
            echo $this->getView()->renderBlock(self::PAGE_BLOCK_NAME);
            $output = ob_get_clean();
            $this->_saveOutputToCache($output);
            echo $output;
            return true;
        }
        return false;
    }

    /**
     *
     * @return bool
     */
    protected function _renderFromCache() {
        if ($this->_shouldRender && $output = $this->_loadOutputFromCache()) {
            echo $this->getView()->fillBlockPlaceholders($output);
            $this->_shouldRender = false;
            return true;
        }
        return false;
    }

    public function canCacheOutput($canCache = true) {
        $this->_canCacheOutput = $canCache ? true :false;
    }

    protected function _saveOutputToCache($output) {
        if ($this->_canCacheOutput) {
            $cacheKey = $this->_getRequestCacheKey();
            Main::app()->saveCache(
                self::CACHE_REQUEST_OUTPUT_PREFIX . $cacheKey,
                $output,
                self::CACHE_REQUEST_OUTPUT_TTL
            );
        }
    }

    /**
     *
     * @return mixed
     */
    protected function _loadOutputFromCache() {
        if ($this->_canCacheOutput) {
            $cacheKey = $this->_getRequestCacheKey();
            return Main::app()->loadCache(self::CACHE_REQUEST_OUTPUT_PREFIX . $cacheKey);
        }
        return false;
    }

    protected function _getRequestCacheKey() {
        return $this->_fw->hash(implode('|', $this->_getRequestCacheKeyInfo()));
    }

    protected function _getRequestCacheKeyInfo() {
        return array(
            $this->_fw->get('REALM'),
            $this->getSession()->getLocale()
        );
    }

    protected function _saveCache($key, $data, $ttl = self::CACHE_DEFAULT_TTL, $strictFullActionName = true) {
        if ($strictFullActionName) {
            $key = $this->getFullActionName() . '_' . $key;
        }
        Main::app()->saveCache($key, $data, $ttl);
        return $this;
    }

    protected function _loadCache($key, $strictFullActionName = true) {
        if ($strictFullActionName) {
            $key = $this->getFullActionName() . '_' . $key;
        }
        return Main::app()->loadCache($key);
    }

    public function getUrl($path, $params = null) {
        return Url::instance()->getUrl($path, $params);
    }

    /**
     * @param $path
     * @return string
     */
    public function autocompleteUrlPath($path) {
        $matches = null;
        $parts = explode('/', $path);
        if (!isset($parts[0])) {
            $parts[0] = 'index';
        }
        elseif ($parts[0] == '*') {
            $parts[0] = $this->getControllerName();
        }
        if (!isset($parts[1])) {
            $parts[1] = 'index';
        }
        elseif (isset($parts[1]) && $parts[1] == '*') {
            $parts[1] = $this->getActionName();
        }
        if (count($parts) == 3 && $parts[2] == '*' && isset($this->_rawRequestParams[2])) {
            $parts[2] = $this->_rawRequestParams[2];
        }
        return implode('/', $parts);
    }

    public function __($string, $vars = null) {
        return I18n::instance()->tr($string, $vars);
    }

    protected function _info($message, $vars = null) {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_INFO);
        return $this;
    }

    protected function _warn($message, $vars = null) {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_WARN);
        return $this;
    }

    protected function _error($message, $vars = null) {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_ERROR);
        return $this;
    }

    protected function _getReferer() {
        $referrer = $this->_fw->get('SERVER.HTTP_REFERER');
        if ($referrer && $referrer != $this->_fw->get('REALM')) {
            return $referrer;
        }
        return null;
    }

    protected function _redirectReferer() {
        if ($referrer = $this->_getReferer()) {
            $this->_rerouteUrl($referrer);
        }
        else {
            $this->_reroute('/');
        }
        return $this;
    }

    protected function _reroute($path, $permanent = false) {
        $url = $this->getUrl($path, array('_force_scheme' => true));
        $this->_rerouteUrl($url, $permanent);
    }

    protected function _rerouteUrl($url, $permanent = false) {
        $this->_fw->reroute($url, $permanent);
    }

    function __call($name, $arguments) {
        if (strcasecmp(substr($name, -6), 'action') === 0) {
            // Tried to call an undefined action, return 404 (instead of 405 from standard)
            if ($this->_fw->get('DEBUG') > 1) {
                Log::instance()->log(
                    'Invalid action requested "' .$name . '" for controller "' . get_class($this) . '".',
                    LOG_INFO
                );
            }
            $this->_fw->error(404);
            return false;
        }
    }
}