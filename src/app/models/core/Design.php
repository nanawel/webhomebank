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

    protected $_themes = null;

    protected $_bodyClass = '';

    protected $_externals = [
        'header' => [],
        'footer' => []
    ];

    public function init(): void {
        $this->_themesDir = Main::app()->getConfig('THEMES_DIR');
        if (!$this->_themesDir) {
            $this->_themesDir = self::DEFAULT_THEMES_DIR;
        }

        $this->_appendThemesToUi()
            ->_runThemeInit();
    }

    protected function _appendThemesToUi(): self {
        $fw = \Base::instance();
        if (!$fw->get('UI_ORIG')) {
            $ui = $fw->get('UI');
            $fw->set('UI_ORIG', $ui);
        }
        else {
            $ui = $fw->get('UI_ORIG');
        }

        $uiPaths = explode(';', $ui);
        $newUiPaths = [];
        foreach($uiPaths as $uiPath) {
            $newUiPaths[] = $uiPath . DIRECTORY_SEPARATOR . $this->getTheme() . DIRECTORY_SEPARATOR;
            $newUiPaths[] = $uiPath . DIRECTORY_SEPARATOR . self::DEFAULT_THEME . DIRECTORY_SEPARATOR;
        }

        $fw->set('UI', implode(';', $newUiPaths));
        return $this;
    }

    protected function _runThemeInit(): self {
        try {
            \View::instance()->render('_init.php', 'text/html', []);
        }
        catch (\Exception) {}

        return $this;
    }

    public function addBodyClass($class): void {
        $classes = explode(' ', $this->_bodyClass);
        $classes[] = $class;
        $this->_bodyClass = implode(' ', $classes);
    }

    public function setBodyClass($newClass): void {
        $this->_bodyClass = $newClass;
    }

    public function getBodyClass() {
        return $this->_bodyClass;
    }

    public function getFaviconUrl() {
        return $this->getImageUrl(Main::app()->getConfig('FAVICON'));
    }

    public function setTheme($theme): self {
        Main::app()->setConfig('THEME', $theme);
        return $this;
    }

    public function getTheme() {
        return Main::app()->getConfig('THEME');
    }

    public function getAvailableThemes() {
        if (!$this->_themes) {
            $uiDirs = \Base::instance()->get('UI_ORIG') ?: \Base::instance()->get('UI');
            $themes = [];
            foreach(explode(';', $uiDirs) as $uiDir) {
                $dir = new \DirectoryIterator($uiDir);
                foreach ($dir as $themeDir) {
                    if($themeDir->isDot()) {
                        continue;
                    }

                    if ($themeDir->isDir()) {
                        $themes[] = $themeDir->getFilename();
                    }
                }
            }

            $this->_themes = array_unique($themes);
        }

        return $this->_themes;
    }

    public function getThemePath($path, $theme = null): string {
        return $this->_themesDir
            . ($theme ?? $this->getTheme()) . DIRECTORY_SEPARATOR
            . trim($path, DIRECTORY_SEPARATOR);
    }

    public function getThemeUrl($path, $fallbackDefault = true) {
        if (str_contains('//', (string) $path)) {    // Full URL (for externals; handles "same protocol as current page" syntax)
            return $path;
        }

        $theme = $this->getTheme();
        if ($fallbackDefault && !file_exists($this->getThemePath($path, $theme))) {
            $theme = self::DEFAULT_THEME;
        }

        $themedPath = $this->getThemePath($path, $theme);
        return Url::instance()->getUrl($themedPath, ['_skip_scheme' => true]);
    }

    public function getCssUrl(string $path) {
        // Prefix has been removed after migrating to Webpack
        return $this->getThemeUrl($path);
    }

    public function getJsUrl(string $path) {
        // Prefix has been removed after migrating to Webpack
        return $this->getThemeUrl($path);
    }

    public function getImageUrl(string $path) {
        return $this->getThemeUrl('images/' . $path);
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addCss($filepath, $area = 'header', $order = 0): self {
        $this->addItems($filepath, 'css', $area, $order);
        return $this;
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addInlineCss($filepath, $area = 'header', $order = 0): self {
        $this->addItems($filepath, 'css_inline', $area, $order);
        return $this;
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addJs($filepath, $area = 'header', $order = 0): self {
        $this->addItems($filepath, 'js', $area, $order);
        return $this;
    }

    /**
     * @param array|string $filepath
     * @param string $area
     * @return $this
     */
    public function addJsModule($filepath, $area = 'header', $order = 0): self {
        $this->addItems($filepath, 'js_module', $area, $order);
        return $this;
    }

    /**
     * @param array|string $content
     * @param string $area
     * @return $this
     */
    public function addInlineJs($content, $area = 'header', $order = 0): self {
        $this->addItems($content, 'js_inline', $area, $order);
        return $this;
    }

    /**
     * @param array|string $content
     * @param string $area
     * @return $this
     */
    public function addInlineJsModule($content, $area = 'header', $order = 0): self {
        $this->addItems($content, 'js_module_inline', $area, $order);
        return $this;
    }

    /**
     * @param object|string|array $items
     * @param $type
     * @param string $area
     */
    public function addItems($items, $type, $area = 'header', $order = 0): self {
        if (!is_array($items)) {
            $items = [$items];
        }

        if (!isset($this->_externals[$area])) {
            $this->_externals[$area] = [];
        }

        if (!isset($this->_externals[$area][$type])) {
            $this->_externals[$area][$type] = [];
        }

        foreach($items as $item) {
            while (isset($this->_externals[$area][$type][$order])) {
                $order++;
            }

            $this->_externals[$area][$type][$order] = $item;
        }

        return $this;
    }

    public function renderItems($area = 'header'): string {
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

    protected function _renderItem(string $item, $type): ?string {
        $output = null;
        switch ($type) {
            case 'css':
                $output = '<link rel="stylesheet" href="' . $this->getCssUrl($item) . '" type="text/css">';
                break;
            case 'css_inline':
                $output = sprintf('<style>%s</style>', $item);
                break;
            case 'js':
            case 'js_module':
                $typeAttr = $type == 'js_module' ? ' type="module"' : '';
                $output = sprintf('<script src="%s"%s></script>', $this->getJsUrl($item), $typeAttr);
                break;
            case 'js_inline':
            case 'js_module_inline':
                $typeAttr = $type == 'js_module_inline' ? ' type="module"' : '';
                $output = <<<"EOJS"
                    <script{$typeAttr}>
                    //<![CDATA[
                    {$item}
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
