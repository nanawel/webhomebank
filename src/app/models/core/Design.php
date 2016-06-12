<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 01/07/15
 * Time: 21:17
 */

namespace app\models\core;

class Design extends \Prefab
{
    const DEFAULT_THEME = 'default';
    const DEFAULT_THEMES_DIR    = 'ui/themes/';

    protected $_themesDir = null;

    protected $_bodyClass = '';
    protected $_externals = array(
        'header' => array(),
        'footer' => array()
    );

    public function init() {
        $this->_themesDir = Main::app()->getConfig('THEMES_DIR');
        if (!$this->_themesDir) {
            $this->_themesDir = self::DEFAULT_THEMES_DIR;
        }
        $this->_appendThemesToUi();
        $this->_runThemeInit();
    }

    protected function _appendThemesToUi() {
        $uiPaths = explode(';', \Base::instance()->get('UI'));
        $newUiPaths = array();
        foreach($uiPaths as $uiPath) {
            $newUiPaths[] = $uiPath . DIRECTORY_SEPARATOR . $this->getTheme() . DIRECTORY_SEPARATOR;
            $newUiPaths[] = $uiPath . DIRECTORY_SEPARATOR . self::DEFAULT_THEME . DIRECTORY_SEPARATOR;
        }
        \Base::instance()->set('UI', implode(';', $newUiPaths));
    }

    protected function _runThemeInit() {
        try {
            \View::instance()->render('init.php', 'text/html', array());
        }
        catch (\Exception $e) {}
    }

    public function addBodyClass($class) {
        $classes = explode(' ', $this->_bodyClass);
        $classes[] = $class;
        $this->_bodyClass = implode(' ', $classes);
    }

    public function setBodyClass($newClass) {
        $this->_bodyClass = $newClass;
    }

    public function getBodyClass() {
        return $this->_bodyClass;
    }

    public function getFaviconUrl() {
        return $this->getImageUrl(Main::app()->getConfig('FAVICON'));
    }

    public function getTheme() {
        return Main::app()->getConfig('THEME');
    }

    public function getThemePath($path, $theme = null) {
        return $this->_themesDir
            . ($theme === null ? $this->getTheme() : $theme) . DIRECTORY_SEPARATOR
            . trim($path, DIRECTORY_SEPARATOR);
    }

    public function getThemeUrl($path, $fallbackDefault = true) {
        if (strpos('//', $path) !== false) {    // Full URL (for externals; handles "same protocol as current page" syntax)
            return $path;
        }
        $theme = $this->getTheme();
        if ($fallbackDefault && !file_exists($this->getThemePath($path, $theme))) {
            $theme = self::DEFAULT_THEME;
        }
        $themedPath = $this->getThemePath($path, $theme);
        return Url::instance()->getUrl($themedPath, array('_skip_scheme' => true));
    }

    public function getCssUrl($path) {
        return $this->getThemeUrl('css/' . $path);
    }

    public function getJsUrl($path) {
        return $this->getThemeUrl('js/' . $path);
    }

    public function getImageUrl($path) {
        return $this->getThemeUrl('images/' . $path);
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addCss($filepath, $area = 'header', $order = 0) {
        $this->addItems($filepath, 'css', $area, $order);
        return $this;
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addJs($filepath, $area = 'header', $order = 0) {
        $this->addItems($filepath, 'js', $area, $order);
        return $this;
    }

    /**
     * @param array|string $content
     * @param string $area
     * @return $this
     */
    public function addInlineJs($content, $area = 'header', $order = 0) {
        $this->addItems($content, 'js_inline', $area, $order);
        return $this;
    }

    /**
     * @param object|string|array $items
     * @param $type
     * @param string $area
     */
    public function addItems($items, $type, $area = 'header', $order = 0) {
        if (!is_array($items)) {
            $items = array($items);
        }
        if (!isset($this->_externals[$area])) {
            $this->_externals[$area] = array();
        }
        if (!isset($this->_externals[$area][$type])) {
            $this->_externals[$area][$type] = array();
        }
        foreach($items as $item) {
            while (isset($this->_externals[$area][$type][$order])) {
                $order++;
            }
            $this->_externals[$area][$type][$order] = $item;
        }
        return $this;
    }

    public function renderItems($area = 'header') {
        $html = '';
        if (isset($this->_externals[$area])) {
            foreach($this->_externals[$area] as $type => $items) {
                ksort($items, SORT_NUMERIC);
                foreach($items as $item) {
                    $html .= $this->_renderItem($item, $type) . "\n";
                }
            }
        }
        return $html;
    }

    protected function _renderItem($item, $type) {
        $output = null;
        switch ($type) {
            case 'css':
                $output = '<link rel="stylesheet" href="' . $this->getCssUrl($item) . '" type="text/css" />';
                break;
            case 'js':
                $output = '<script src="' . $this->getJsUrl($item) . '" type="application/javascript"></script>';
                break;
            case 'js_inline':
                $output = <<<"EOJS"
<script type="application/javascript">
//<![CDATA[
$item
//]]>
</script>
EOJS;
                break;
            default:
                // ?
                break;
        }
        return $output;
    }
}