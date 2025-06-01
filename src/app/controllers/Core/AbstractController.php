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

    protected $_controller = null;

    protected $_action = null;

    protected $_rawRequestParams;

    protected $_requestParams = [];

    protected $_viewInstance = null;

    protected $_shouldRender = true;

    protected $_renderedFromCache = true;

    protected $_canCacheOutput = true;

    public final function __construct(protected $fw) {
        Main::instance()->setup();
        Main::app()->setCurrentController($this);
        $this->setPageTemplate(self::PAGE_TEMPLATE_DEFAULT);
        $this->_init();
    }

    protected function _init(): void {
        // to be overridden
    }

    public function getSession($name = null): Session {
        return Main::app()->getSession($name);
    }

    protected function _initRequestData($args): void {
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

    public function getControllerName(): string {
        return $this->_controller;
    }

    public function getActionName(): string {
        return $this->_action;
    }

    protected function _setRequestParams(array $params): self {
        $parts = array_chunk($params, 2);
        $keys = array_column($parts, 0);
        $values = array_column($parts, 1);

        if (count($values) < count($keys)) {
            $values[] = null;
        }

        $this->_requestParams = array_combine($keys, $values);
        return $this;
    }

    protected function _getRequestParams(): array {
        return $this->_requestParams;
    }

    protected function _getRequestParam($param): ?string {
        return $this->_requestParams[$param] ?? null;
    }

    public function getRequestQuery($param = null): string|array|null {
        $query = $this->fw->get('REQUEST');
        if ($param === null) {
            return $query;
        }

        return $query[$param] ?? null;
    }

    public function getFullActionName(): string {
        return $this->_controller . '/' . $this->_action;
    }

    protected function _setContentTemplate(): self {
        $fullAction = $this->getFullActionName();
        $defaultTemplate = str_replace(['_', '/'], DIRECTORY_SEPARATOR, $fullAction) . View::TEMPLATE_DEFAULT_EXTENSION;
        $this->getView()->setBlockTemplate(View::TEMPLATE_CONTENT_KEY, $defaultTemplate);
        return $this;
    }

    protected function _addBodyClass(): void {
        $class = preg_replace('/[^a-z-]/i', '-', $this->_controller);
        Design::instance()->addBodyClass($class);
        $class = preg_replace('/[^a-z-]/i', '-', $this->getFullActionName());
        Design::instance()->addBodyClass($class);
    }

    public function getPageTitle(): string {
        return $this->fw->get('PAGE_TITLE');
    }

    public function setPageTitle(string $title): self {
        $this->fw->set('PAGE_TITLE', $title);
        return $this;
    }

    public function setShouldRender(bool $shouldRender): self {
        $this->_shouldRender = $shouldRender;
        return $this;
    }

    public function getShouldRender(): bool {
        return $this->_shouldRender;
    }

    /**
     * @param string $template
     */
    public function setPageTemplate($template): self {
        $this->getView()->setBlockTemplate(self::PAGE_BLOCK_NAME, $template);
        return $this;
    }

    /**
     * @param string $template
     */
    public function setPageConfig($config): self {
        $this->getView()->setBlockConfig(self::PAGE_BLOCK_NAME, $config);
        return $this;
    }

    /**
     * @return string
     */
    public function getPageTemplate(): string {
        return $this->getView()->getBlockTemplate(self::PAGE_BLOCK_NAME);
    }

    /**
     * @param \Base $fw
     * @param string $args
     */
    #[\ReturnTypeWillChange]
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
        if (method_exists($this, $beforeMethod) && $this->$beforeMethod($fw, $args)) {
            return false;
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
    #[\ReturnTypeWillChange]
    public final function afterRoute($fw, $args = null) {
        if ($this->_afterRoute($fw, $args) === false) {
            return false;
        }

        $this->_render();
        $this->__afterRender($fw, $args);
        return null;
    }

    private function __afterRender($fw, $args = null): void {
        $this->getSession()->set('last_url', $fw->get('REALM'));
        $this->_afterRender($fw, $args);
    }

    /**
     * @param $fw
     * @param null $args
     * @return bool False to stop request processing.
     */
    #[\ReturnTypeWillChange]
    protected function _beforeRoute($fw, $args = null) {
        // to be overridden
    }

    /**
     * @param $fw
     * @param null $args
     * @return bool False to stop request processing.
     */
    #[\ReturnTypeWillChange]
    protected function _afterRoute($fw, $args = null) {
        // to be overridden
    }

    #[\ReturnTypeWillChange]
    protected function _afterRender($fw, $args = null) {
        // to be overridden
    }

    public function getView(): View {
        return $this->_viewInstance ?: View::instance();
    }

    protected function _render(): bool {
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
    protected function _renderFromCache(): bool {
        if ($this->_shouldRender && $output = $this->_loadOutputFromCache()) {
            echo $this->getView()->fillBlockPlaceholders($output);
            $this->_shouldRender = false;
            return true;
        }

        return false;
    }

    public function canCacheOutput($canCache = true): void {
        $this->_canCacheOutput = (bool) $canCache;
    }

    protected function _saveOutputToCache($output): void {
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
    protected function _loadOutputFromCache(): string|false {
        if ($this->_canCacheOutput) {
            $cacheKey = $this->_getRequestCacheKey();
            return Main::app()->loadCache(self::CACHE_REQUEST_OUTPUT_PREFIX . $cacheKey);
        }

        return false;
    }

    protected function _getRequestCacheKey(): string {
        return $this->fw->hash(implode('|', $this->_getRequestCacheKeyInfo()));
    }

    protected function _getRequestCacheKeyInfo(): array {
        return [
            $this->fw->get('REALM'),
            $this->getSession()->getLocale()
        ];
    }

    protected function _saveCache($key, $data, $ttl = self::CACHE_DEFAULT_TTL, $strictFullActionName = true): self {
        if ($strictFullActionName) {
            $key = $this->getFullActionName() . '_' . $key;
        }

        Main::app()->saveCache($key, $data, $ttl);
        return $this;
    }

    protected function _loadCache($key, $strictFullActionName = true): mixed {
        if ($strictFullActionName) {
            $key = $this->getFullActionName() . '_' . $key;
        }

        return Main::app()->loadCache($key);
    }

    public function getUrl($path, $params = null): string {
        return Url::instance()->getUrl($path, $params);
    }

    public function autocompleteUrlPath(string $path): string {
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

        if (count($parts) == 3 && $parts[2] == '*') {
            if (isset($this->_rawRequestParams['*'])) {
                $parts[2] = $this->_rawRequestParams['*'];
            } else {
                unset($parts[2]);
            }
        }

        return implode('/', $parts);
    }

    public function __(?string $string, $vars = null): string {
        return I18n::instance()->tr($string, $vars);
    }

    protected function _info(?string $message, $vars = null): void {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_INFO);
    }

    protected function _warn(?string $message, $vars = null): void {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_WARN);
    }

    protected function _error(?string $message, $vars = null): void {
        $this->getSession()->addMessage($this->__($message, $vars), Session::MESSAGE_ERROR);
    }

    protected function _getReferer(): ?string {
        $referrer = $this->fw->get('SERVER.HTTP_REFERER');
        if ($referrer && $referrer != $this->fw->get('REALM')) {
            return $referrer;
        }

        return null;
    }

    protected function _redirectReferer(): void {
        if ($referrer = $this->_getReferer()) {
            $this->_rerouteUrl($referrer);
        }
        else {
            $this->_reroute('/');
        }
    }

    protected function _reroute($path, $permanent = false): void {
        $url = $this->getUrl($path, ['_force_scheme' => true]);
        $this->_rerouteUrl($url, $permanent);
    }

    protected function _rerouteUrl($url, $permanent = false): void {
        $this->fw->reroute($url, $permanent);
    }

    public function __call(string $name, $arguments): mixed {
        if (strcasecmp(substr($name, -6), 'action') === 0) {
            // Tried to call an undefined action, return 404 (instead of 405 from standard)
            if ($this->fw->get('DEBUG') > 1) {
                Log::instance()->log(
                    'Invalid action requested "' .$name . '" for controller "' . static::class . '".',
                    LOG_INFO
                );
            }

            $this->fw->error(404);
            return false;
        }

        return null;
    }
}
