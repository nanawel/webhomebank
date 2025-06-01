<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 03/07/15
 * Time: 22:20
 */

namespace app\models\core;

use app\helpers\core\Output;

class View extends \View
{
    const TEMPLATE_DEFAULT_EXTENSION = '.phtml';

    const TEMPLATE_CONTENT_KEY = 'content';

    const TEMPLATE_BASE_DIR = 'templates';

    const BLOCK_KEY_PREFIX = 'BLOCK.';

    const BLOCK_PLACEHOLDER_KEY_PREFIX = 'BLOCK_PLACEHOLDER.';

    const CACHE_BLOCK_OUTPUT_PREFIX = 'BLOCK_';

    protected $_data = [];

    protected $_placeholdersRendering = false;

    /**
     * @return I18n
     */
    public function i18n() {
        return I18n::instance();
    }

    public function __($string, $vars = null): mixed {
        return call_user_func_array(
            [$this->i18n(), 'tr'],
            func_get_args()
        );
    }

    public function getUrl($path, $params = []): string {
        return Url::instance()->getUrl($path, $params);
    }

    /**
     * @param string $blockName
     * @return mixed
     */
    public function getBlockConfig(string $blockName, $configKey = null) {
        if ($configKey === null) {
            $config = \Base::instance()->get(self::BLOCK_KEY_PREFIX . $blockName);
            if (!$config) {
                $config = [];
            }
        }
        else {
            $config = \Base::instance()->get(self::BLOCK_KEY_PREFIX . $blockName . '.' . $configKey);
        }

        return $config;
    }

    /**
     * @param string $blockName
     * @param mixed|array $configKey
     * @param mixed|null $value
     * @return $this
     */
    public function setBlockConfig(string $blockName, $configKey, $value = null): self {
        if ($value === null) {
            \Base::instance()->set(self::BLOCK_KEY_PREFIX . $blockName, $configKey);
        }
        else {
            \Base::instance()->set(self::BLOCK_KEY_PREFIX . $blockName . '.' . $configKey, $value);
        }

        return $this;
    }

    /**
     * @param string $blockName
     * @return string
     */
    public function getBlockTemplate(string $blockName) {
        return $this->getBlockConfig($blockName, 'template');
    }

    /**
     * @param string $blockName
     * @param string $template
     */
    public function setBlockTemplate(string $blockName, $template): self {
        $this->setBlockConfig($blockName, 'template', $template);
        return $this;
    }

    /**
     * @param $blockName
     * @param callable $callable
     * @param array $params
     * @return $this
     */
    public function setBlockCachePlaceholder(string $blockName, $callable = null, $params = []): self {
        \Base::instance()->set(self::BLOCK_PLACEHOLDER_KEY_PREFIX . $blockName, [$callable, $params]);
        return $this;
    }

    public function getBlockCachePlaceholder(string $blockName) {
        return \Base::instance()->get(self::BLOCK_PLACEHOLDER_KEY_PREFIX . $blockName);
    }

    public function isRenderingPlaceholders() {
        return $this->_placeholdersRendering;
    }

    /**
     * Render block using cache placeholder if available.
     *
     * @param $blockName
     * @param string $mime
     * @param array $hive
     * @param int $ttl
     * @return string
     */
    public function renderBlock(string $blockName, $mime = 'text/html', array $hive = null, $ttl = 0) {
        $output = null;
        $shouldRender = true;
        static $processingPlaceholders = [];

        if ($this->isRenderingPlaceholders() && ($placeholderConfig = $this->getBlockCachePlaceholder($blockName)) && $placeholderConfig[0] !== null) {
            if (is_callable($placeholderConfig[0])) {
                if (isset($processingPlaceholders[$blockName]) && $processingPlaceholders[$blockName]) {
                    $shouldRender = false;
                }
                else {
                    $processingPlaceholders[$blockName] = true;
                    $output = call_user_func($placeholderConfig[0], $placeholderConfig[1]);
                    $processingPlaceholders[$blockName] = false;

                    if ($output === false) {
                        $output = '';           //Overwrite with clean string
                    }

                    $shouldRender = false;
                }
            }
            else {
                Log::instance()->log(
                    'Invalid placeholder callback for block "' . $blockName . '": ' .
                    \Base::instance()->stringify($placeholderConfig[0])
                );
            }
        }

        if ($shouldRender) {
            $output = $this->renderBlockWithoutCache($blockName, $mime, $hive, $ttl);
        }

        return $output;
    }

    /**
     * Render block using cache placeholder if available.
     *
     * @param $blockName
     * @param string $mime
     * @param array $hive
     * @param int $ttl
     * @return string
     */
    public function renderBlockWithoutCache(string $blockName, $mime = 'text/html', array $hive = null, $ttl = 0) {
        $hasPlaceholder = (bool) $this->getBlockCachePlaceholder($blockName);
        $blockConfig = $this->getBlockConfig($blockName);
        $output = '';
        try {
            if (!isset($blockConfig['template']) || !$blockConfig['template']) {
                throw new \Exception('Missing template for block "' . $blockName . '".');
            }

            $mime = (isset($blockConfig['mime']) && $blockConfig['mime']) ? $blockConfig['mime'] : 'text/html';

            //TODO Data should be moved to block's config
            $hive = $hive !== null ? array_merge($this->getData(), $hive) : $this->getData();
            $hive['_BLOCKNAME'] = $blockName;
            $output = $this->render($blockConfig['template'], $mime, $hive, $ttl);

            if ($hasPlaceholder) {
                $output = $this->_wrapBlockPlaceholder($blockName, $output, $mime);
            }
        }
        catch (\Exception $exception) {
            /*if ($mime == 'text/html') {
                $exClass = get_class($e);
                $output = "<!-- {$exClass} [{$e->getCode()}] {$e->getMessage()} -->";
            }*/
            Log::instance()->logException($exception);
        }

        return $output;
    }

    protected function _wrapBlockPlaceholder($blockName, $blockOutput, $mime = 'text/html') {
        // Only mimetype supported for now
        if ($mime == 'text/html') {
            $blockOutput = $this->_getBlockPlaceholderBanner($blockName, 'BEGIN') . "\n" .
                $blockOutput . "\n" .
                $this->_getBlockPlaceholderBanner($blockName, 'END') . "\n";
        }

        return $blockOutput;
    }

    public function fillBlockPlaceholders($cacheOutput, $mime = 'text/html') {
        try {
            $this->_placeholdersRendering = true;
            // Only mimetype supported for now
            if ($mime == 'text/html') {
                $dummyBlockName = '@@BLOCKNAME_PATTERN_HERE@@';
                $beginBannerPattern = str_replace(
                    $dummyBlockName,
                    '(?P<blockName>[a-z0-9_]+)',
                    preg_quote($this->_getBlockPlaceholderBanner($dummyBlockName, 'BEGIN', $mime), '#')
                );
                $endBannerPattern = str_replace(
                    $dummyBlockName,
                    '\1',
                    preg_quote($this->_getBlockPlaceholderBanner($dummyBlockName, 'END', $mime), '#')
                );
                $pattern = sprintf('#%s(?P<blockContent>.*?)%s#sm', $beginBannerPattern, $endBannerPattern);
                preg_match_all($pattern, $cacheOutput, $matches);
                $generatedOutput = preg_replace_callback(
                    $pattern,
                    fn(array $matches) => $this->renderBlock($matches['blockName'], $mime),
                    $cacheOutput
                );
                $finalOutput =& $generatedOutput;
            }
            else {
                $finalOutput =& $cacheOutput;
            }
        }
        finally {
            $this->_placeholdersRendering = false;
        }

        return $finalOutput;
    }

    protected function _getBlockPlaceholderBanner($blockName, $suffix, $mime = 'text/html'): string {
        // Only mimetype supported for now
        if ($mime == 'text/html') {
            return sprintf('<!-- {{ BLOCK_PLACEHOLDER_%s_%s }} -->', $blockName, $suffix);
        }

        return '';
    }

    public function getMessagesHtml($session = null, $flush = true): string {
        if ($session !== null) {
            $sessions = is_array($session) ? $session : [$session];
        }
        else {
            $sessions = Main::app()->getSessions();
        }

        $html = '';
        foreach($sessions as $session) {
            $messagesByType = $session->getMessages();
            foreach($messagesByType as $type => $messages) {
                foreach($messages as $m) {
                    $message = $m['message'];
                    $options = $m['options'];
                    if (!isset($options['no_escape']) || !$options['no_escape']) {
                        $message = Output::htmlspecialchars($message);
                    }

                    $html .= sprintf('<div class="message-%s"><span>', $type) . $message . "</span></div>\n";
                }
            }

            if ($flush) {
                $session->clearMessages();
            }
        }

        return $html;
    }

    /**
     * @return \app\controllers\Core\AbstractController
     */
    public function getCurrentController() {
        return Main::app()->getCurrentController();
    }

    public function getData($key = null) {
        if ($key === null) {
            return $this->_data;
        }

        return $this->_data[$key] ?? null;
    }

    public function setData($key, $value): self {
        if ($key === null) {
            $this->_data = $value;
        }
        else {
            $this->_data[$key] = $value;
        }

        return $this;
    }

    public function hasData($key): bool {
        return isset($this->_data[$key]);
    }
}
